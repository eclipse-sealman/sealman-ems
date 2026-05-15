<?php

// Copyright (c) 2025 Contributors to the Eclipse Foundation.
//
// See the NOTICE file(s) distributed with this work for additional
// information regarding copyright ownership.
//
// This program and the accompanying materials are made available under the
// terms of the Apache License, Version 2.0 which is available at
// https://www.apache.org/licenses/LICENSE-2.0
//
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace App\Service;

use App\Entity\Certificate;
use App\Entity\CertificateType;
use App\Entity\Device;
use App\Entity\DeviceTypeCertificateType;
use App\Entity\User;
use App\Enum\PkiHashAlgorithm;
use App\Enum\PkiKeyType;
use App\Enum\PkiType;
use App\Exception\LogsException;
use App\Exception\ProviderException;
use App\Provider\Interface\PkiProviderInterface;
use App\Provider\ScepPkiProvider;
use App\Service\Helper\CertificateManagerTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EncryptionManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\FileManagerTrait;
use App\Service\Helper\HttpClientTrait;
use App\Service\Helper\OpenSslManagerTrait;
use App\Service\Helper\PkiProviderFactoryTrait;
use App\Service\Helper\SymfonyDirTrait;
use App\Service\Helper\VpnLogManagerTrait;
use App\Service\Helper\VpnManagerTrait;
use App\Service\Trait\CertificateTypeHelperTrait;
use App\Tool\Urlizer;
use Carve\ApiBundle\Exception\RequestExecutionException;
use Carve\ApiBundle\Helper\Arr;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PkiProvidersManager
{
    use ConfigurationManagerTrait;
    use CertificateManagerTrait;
    use CertificateTypeHelperTrait;
    use EntityManagerTrait;
    use EncryptionManagerTrait;
    use SymfonyDirTrait;
    use VpnLogManagerTrait;
    use VpnManagerTrait;
    use HttpClientTrait;
    use OpenSslManagerTrait;

    use FileManagerTrait;
    use PkiProviderFactoryTrait;

    public function getCaCertificateForCertificateType(CertificateType $certificateType): ?string
    {
        if ($this->configurationManager->isScepBlocked()) {
            throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.invalidLicense', ['certificateType' => $certificateType->getRepresentation()]));
        }

        $provider = $this->getPkiProviderByCertificateType($certificateType);

        try {
            return $provider->getCaCertificate();
        } catch (ProviderException $providerException) {
            throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.caRequestFailed', ['certificateType' => $certificateType->getRepresentation(), 'exceptionMessage' => $providerException->getMessage()]));
        }
    }

    public function getCrlContentForCertificateType(CertificateType $certificateType): ?string
    {
        if ($this->configurationManager->isScepBlocked()) {
            throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.invalidLicense', ['certificateType' => $certificateType->getRepresentation()]));
        }

        $provider = $this->getPkiProviderByCertificateType($certificateType);

        try {
            return $provider->getCrl();
        } catch (ProviderException $providerException) {
            throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.crlRequestFailed', ['certificateType' => $certificateType->getRepresentation(), 'exceptionMessage' => $providerException->getMessage()]));
        }
    }

    public function generateCertificate(Certificate $certificate): void
    {
        if ($this->configurationManager->isScepBlocked()) {
            throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.invalidLicense', certificate: $certificate));
        }

        if ($certificate->hasAnyCertificatePart()) {
            throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.certificateExists', certificate: $certificate));
        }

        $provider = $this->getPkiProvider($certificate);

        try {
            $caCertificatePem = $provider->getCaCertificate();

            $caCertificateData = openssl_x509_parse($caCertificatePem);
            if (!isset($caCertificateData['subject']) || !is_array($caCertificateData['subject'])) {
                throw new ProviderException($provider->addLogCritical('log.pkiProviders.caInvalid'));
            }

            $certificateSubject = $this->getCertificateSubject($certificate);

            $provider->addLogInfo('log.pkiProviders.certificateRequested', ['certificateSubject' => $certificateSubject]);

            $hashAlgorithm = $this->getHashAlgorithm($certificate);
            $keyType = $this->getKeyType($certificate);
            $csrSubject = $this->openSslManager->getFullSubjectStringWithUpdatedCommonName($caCertificateData, $certificateSubject);
            $csrAdditionalText = $this->getCsrAdditionalTextForSubjectAlt($certificate);

            $privateKey = $this->openSslManager->generatePrivateKey($keyType);
            $csr = $this->openSslManager->generateCsr(
                keyType: $keyType,
                hashAlgorithm: $hashAlgorithm,
                privateKey: $privateKey,
                subject: $csrSubject,
                addText: $csrAdditionalText,
            );

            // Using SHA512 and RSA4096 for signing CSR to have stronger certificate even if CSR was created with lower parameters - SCEP is not fully supporting EC and other algorithms
            $signedCertificatePem = $provider->signCsr(PkiHashAlgorithm::SHA512, PkiKeyType::RSA4096, $caCertificatePem, $csr);

            if (!openssl_x509_check_private_key($signedCertificatePem, $privateKey)) {
                throw new ProviderException($provider->addLogCritical('log.pkiProviders.pairCheckFailed'));
            }

            $certificate->setCertificateCaSubject(Arr::get($caCertificateData, 'subject.CN', 'unknown'));
            $certificate->setCertificate($this->encryptionManager->encrypt($signedCertificatePem));
            $certificate->setCertificateCa($this->encryptionManager->encrypt($caCertificatePem));
            $certificate->setCertificateGenerated(true);
            $certificate->setPrivateKey($this->encryptionManager->encrypt($privateKey));
            $certificate->setCertificateSubject($certificateSubject);

            $signedCertificateData = openssl_x509_parse($signedCertificatePem);
            $validTo = new \DateTime();
            $validTo->setTimestamp($signedCertificateData['validTo_time_t']);

            $certificate->setCertificateValidTo($validTo);

            // Adding certificate entity to it's target in case it was not added yet (persist of target is not needed)
            $certificate->getTarget()->addCertificate($certificate);

            $this->entityManager->persist($certificate);
            $this->entityManager->flush();

            $this->vpnLogManager->createLogs($provider->getLogs(), certificate: $certificate);

            $this->vpnLogManager->createLogInfo('log.pkiProviders.certificateRequestSuccess', certificate: $certificate);
        } catch (ProcessFailedException $exception) {
            $log = $this->vpnLogManager->createLogError(
                'log.pkiProviders.consoleCommandFailed',
                [
                    'commandString' => $exception->getProcess()->getCommandLine(),
                    'exceptionMessage' => $exception->getMessage(),
                ],
                certificate: $certificate,
            );
            throw new LogsException($log);
        } catch (ProviderException $providerException) {
            // log request failed
            $this->vpnLogManager->createLogs($provider->getLogs(), certificate: $certificate);

            throw new LogsException($providerException);
        } catch (\Exception $exception) {
            // unknown error - log it as critical
            $log = $this->vpnLogManager->createLogCritical(
                'log.pkiProviders.unknownException',
                [
                    'exceptionMessage' => $exception->getMessage(),
                ],
                certificate: $certificate,
            );

            throw new LogsException($log);
        }
    }

    protected function getCsrAdditionalTextForSubjectAlt(Certificate $certificate): array
    {
        $device = $certificate->getDevice();
        if (!$device) {
            return [];
        }

        $deviceTypeCertificateType = $this->getRepository(DeviceTypeCertificateType::class)->findOneBy([
            'deviceType' => $device->getDeviceType(),
            'certificateType' => $certificate->getCertificateType(),
        ]);
        if (!$deviceTypeCertificateType) {
            return [];
        }

        $type = $deviceTypeCertificateType->getSubjectAltNameType();
        $value = $deviceTypeCertificateType->getSubjectAltNameValue();
        if (!$deviceTypeCertificateType->getEnableSubjectAltName() || !$type || !$value) {
            return [];
        }

        return [
            'subjectAltName = '.$type->value => $value,
        ];
    }

    public function revokeCertificate(Certificate $certificate): void
    {
        if ($this->configurationManager->isScepBlocked()) {
            throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.invalidLicense', certificate: $certificate));
        }
        if (!$certificate->getCertificate() || !$certificate->getCertificateGenerated()) {
            throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.certificateRevocationFailedNoCertificate', certificate: $certificate));
        }

        $provider = $this->getPkiProvider($certificate);

        try {
            $certificateData = openssl_x509_parse($this->encryptionManager->decrypt($certificate->getCertificate()));
            if (!isset($certificateData['serialNumber'])) {
                throw new ProviderException($provider->addLogError('log.pkiProviders.certificateInvalid'));
            }

            $serialNumber = $certificateData['serialNumber'];
            $certificateSubject = $certificate->getCertificateSubject();

            $provider->addLogInfo('log.pkiProviders.certificateRevocationRequested', ['certificateSubject' => $certificateSubject]);

            $provider->revokeCertificate($serialNumber);

            $certificate->setCertificateSubject(null);
            $certificate->setCertificateCaSubject(null);
            $certificate->setCertificate(null);
            $certificate->setCertificateCa(null);
            $certificate->setPrivateKey(null);
            $certificate->setCertificateValidTo(null);
            $certificate->setCertificateGenerated(false);

            $this->entityManager->persist($certificate);

            $this->entityManager->flush();

            $this->vpnLogManager->createLogs($provider->getLogs(), certificate: $certificate);

            $this->vpnLogManager->createLogInfo(
                'log.pkiProviders.certificateRevocationSuccess',
                [
                    'certificateSubject' => $certificateSubject,
                ],
                certificate: $certificate
            );

            $provider->clearLogs();

            $crlResponse = $provider->getCrl();

            $this->vpnLogManager->createLogs($provider->getLogs(), certificate: $certificate);

            // todo make sure exceptions from vpnProvider are handled correctly (assuming logInfo from crl - just for good code)
            $this->vpnManager->processCrlUpdate($certificate, $crlResponse);
        } catch (ProviderException $providerException) {
            // log request failed
            $this->vpnLogManager->createLogs($provider->getLogs(), certificate: $certificate);

            throw new LogsException($providerException);
        }
    }

    public function getCrlByUrl(PkiType $pkiType, string $url, int $scepTimeout, bool $verifyServerSslCertificate): string
    {
        switch ($pkiType) {
            case PkiType::SCEP:
                $crl = ScepPkiProvider::getCrlByUrl($this->httpClient, $url, $scepTimeout, $verifyServerSslCertificate);
                if (null === $crl) {
                    throw new RequestExecutionException('log.pkiProviders.getCrlByUrlFailed', ['url' => $url]);
                }

                return $crl;
            case PkiType::NONE:
            default:
                throw new \Exception('Unsupported PKI protocol type "'.$pkiType->value.'"');
        }
    }

    protected function getCertificateSubject(Certificate $certificate): string
    {
        // certificateEntity is already validated
        switch (true) {
            case $certificate->getTarget() instanceof Device:
                // CommonNamePrefix will be empty for deviceVpn and technicianVpn
                $prefix = ($certificate->getCertificateType()->getCommonNamePrefix() ?: '').$certificate->getDevice()->getDeviceType()->getCertificateCommonNamePrefix();
                $name = $certificate->getDevice()->getName();
                break;
            case $certificate->getTarget() instanceof User:
                // CommonNamePrefix will be empty for deviceVpn and technicianVpn
                $prefix = $certificate->getCertificateType()->getCommonNamePrefix() ?: '';
                $name = $certificate->getUser()->getUsername();
                break;
            default:
                throw new \Exception('Unknown way to get certificate common name for this object');
        }

        // SCEP Server allows common name to be maximum of 53 characters
        $certificateSubject = substr(Urlizer::urlize($prefix.'-'.$name), 0, 53);
        $rowCount = $this->getRepository(Certificate::class)->count(['certificateSubject' => $certificateSubject]);

        $suffix = 1;
        $baseCertificateSubject = $certificateSubject;
        while ($rowCount > 0) {
            // replacing last characters with suffix - common name will still have 53 chars
            $suffixString = '-'.$suffix;
            $certificateSubject = substr_replace($baseCertificateSubject, $suffixString, -strlen($suffixString));

            $rowCount = $this->getRepository(Certificate::class)->count(['certificateSubject' => $certificateSubject]);
            ++$suffix;
        }

        return $certificateSubject;
    }

    protected function getHashAlgorithm(Certificate $certificate): PkiHashAlgorithm
    {
        $certificateType = $certificate->getCertificateType();
        if (!$certificateType) {
            throw new LogsException($this->vpnLogManager->createLogCritical('log.pkiProviders.certificateTypeNotSet', certificate: $certificate));
        }

        $pkiType = $certificateType->getPkiType();
        switch ($pkiType) {
            case PkiType::SCEP:
                if (!$certificateType->getScepHashFunction()) {
                    throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.scep.missingHashFunction', certificate: $certificate));
                }

                return $certificateType->getScepHashFunction();
            case PkiType::NONE:
            default:
                throw new \Exception('Unsupported PKI protocol type "'.$pkiType->value.'"');
        }
    }

    protected function getKeyType(Certificate $certificate): PkiKeyType
    {
        $certificateType = $certificate->getCertificateType();
        if (!$certificateType) {
            throw new LogsException($this->vpnLogManager->createLogCritical('log.pkiProviders.certificateTypeNotSet', certificate: $certificate));
        }

        $pkiType = $certificateType->getPkiType();
        switch ($pkiType) {
            case PkiType::SCEP:
                if (!$certificateType->getScepKeyType()) {
                    throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.scep.missingKeyType', certificate: $certificate));
                }

                return $certificateType->getScepKeyType();
            case PkiType::NONE:
            default:
                throw new \Exception('Unsupported PKI protocol type "'.$pkiType->value.'"');
        }
    }

    // Using Certificate as parameter instead of CertificateType for better logs
    protected function getPkiProvider(Certificate $certificate): PkiProviderInterface
    {
        return $this->pkiProviderFactory->getProvider($certificate);
    }

    protected function getPkiProviderByCertificateType(CertificateType $certificateType): PkiProviderInterface
    {
        return $this->pkiProviderFactory->getProvider(certificateType: $certificateType);
    }
}
