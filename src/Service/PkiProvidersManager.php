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
use App\Entity\Device;
use App\Entity\DeviceTypeCertificateType;
use App\Entity\User;
use App\Enum\PkiHashAlgorithm;
use App\Enum\PkiKeyLength;
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
use App\Service\Helper\SymfonyDirTrait;
use App\Service\Helper\VpnLogManagerTrait;
use App\Service\Helper\VpnManagerTrait;
use App\Service\Trait\CertificateTypeHelperTrait;
use Carve\ApiBundle\Exception\RequestExecutionException;
use Carve\ApiBundle\Helper\Arr;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Filesystem\Filesystem;

class PkiProvidersManager
{
    use ConfigurationManagerTrait;
    use CertificateManagerTrait;
    use CertificateTypeHelperTrait;
    use EntityManagerTrait;
    use EncryptionManagerTrait;
    use FileManagerTrait;
    use SymfonyDirTrait;
    use VpnLogManagerTrait;
    use VpnManagerTrait;
    use HttpClientTrait;

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

            $csrDn = $this->prepareCsrDn($caCertificateData, $certificate, $certificateSubject);

            $hashAlgorithm = $this->getHashAlgorithm($certificate);
            $keyLength = $this->getKeyLength($certificate);

            $openSslConfigPath = $this->projectDir.'/config/openssl/pki_providers_manager.conf';
            $fs = new Filesystem();
            if (!$fs->exists($openSslConfigPath)) {
                throw new \Exception('Missing '.$openSslConfigPath.' file');
            }

            $defaultConfigArgs = [
                'digest_alg' => $hashAlgorithm->value,
                'private_key_bits' => intval($keyLength->value),
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ];

            $privateKey = openssl_pkey_new($defaultConfigArgs);

            // Somehow config file overwrites private key bit size if added before key generation
            // Using custom configuration file because default openSSL configuration adds default values for C,ST,L,O parts of subject
            // Our custom configuration file leaves those fields empty, current code copies values from CA
            $defaultConfigArgs['config'] = $openSslConfigPath;

            $csr = openssl_csr_new($csrDn, $privateKey, $defaultConfigArgs);

            if (false === $csr) {
                throw new ProviderException($provider->addLogCritical('log.pkiProviders.csrGenerationFailed'));
            }

            $signedCertificatePem = $provider->signCsr($hashAlgorithm, $keyLength, $caCertificatePem, $csr);

            openssl_pkey_export($privateKey, $privateKeyPem);

            if (!openssl_x509_check_private_key($signedCertificatePem, $privateKeyPem)) {
                throw new ProviderException($provider->addLogCritical('log.pkiProviders.pairCheckFailed'));
            }

            $certificate->setCertificateCaSubject(Arr::get($caCertificateData, 'subject.CN', 'unknown'));
            $certificate->setCertificate($this->encryptionManager->encrypt($signedCertificatePem));
            $certificate->setCertificateCa($this->encryptionManager->encrypt($caCertificatePem));
            $certificate->setCertificateGenerated(true);
            $certificate->setPrivateKey($this->encryptionManager->encrypt($privateKeyPem));
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
        } catch (ProviderException $providerException) {
            // log request failed
            $this->vpnLogManager->createLogs($provider->getLogs(), certificate: $certificate);

            throw new LogsException($providerException);
        }
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

    protected function prepareCsrDn(array $caCertificateData, Certificate $certificate, string $certificateSubject): array
    {
        $dn = [
            'commonName' => $certificateSubject,
        ];

        if (Arr::has($caCertificateData, 'subject.C')) {
            $dn['countryName'] = Arr::get($caCertificateData, 'subject.C');
        }

        if (Arr::has($caCertificateData, 'subject.ST')) {
            $dn['stateOrProvinceName'] = Arr::get($caCertificateData, 'subject.ST');
        }

        if (Arr::has($caCertificateData, 'subject.L')) {
            $dn['localityName'] = Arr::get($caCertificateData, 'subject.L');
        }

        if (Arr::has($caCertificateData, 'subject.O')) {
            $dn['organizationName'] = Arr::get($caCertificateData, 'subject.O');
        }

        if ($certificate->getDevice()) {
            $deviceTypeCertificateType = $this->getRepository(DeviceTypeCertificateType::class)->findOneBy([
                'deviceType' => $certificate->getDevice()->getDeviceType(),
                'certificateType' => $certificate->getCertificateType(),
            ]);

            if ($deviceTypeCertificateType) {
                if (
                    $deviceTypeCertificateType->getEnableSubjectAltName() &&
                    $deviceTypeCertificateType->getSubjectAltNameType() &&
                    $deviceTypeCertificateType->getSubjectAltNameValue()
                ) {
                    $dn['subjectAltName'] = $deviceTypeCertificateType->getSubjectAltNameType()->value.':'.$deviceTypeCertificateType->getSubjectAltNameValue();
                }
            }
        }

        return $dn;
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

    protected function getCertificateRequestDir(): string
    {
        $path = $this->projectDir.'/private/certificate_request/';

        $fs = new Filesystem();
        if (!$fs->exists($path)) {
            $fs->mkdir($path);
        }

        return $path;
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

    protected function getKeyLength(Certificate $certificate): PkiKeyLength
    {
        $certificateType = $certificate->getCertificateType();
        if (!$certificateType) {
            throw new LogsException($this->vpnLogManager->createLogCritical('log.pkiProviders.certificateTypeNotSet', certificate: $certificate));
        }

        $pkiType = $certificateType->getPkiType();
        switch ($pkiType) {
            case PkiType::SCEP:
                if (!$certificateType->getScepKeyLength()) {
                    throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.scep.missingKeyLength', certificate: $certificate));
                }

                return $certificateType->getScepKeyLength();
            case PkiType::NONE:
            default:
                throw new \Exception('Unsupported PKI protocol type "'.$pkiType->value.'"');
        }
    }

    // Using Certificate as parameter instead of CertificateType for better logs
    protected function getPkiProvider(Certificate $certificate): PkiProviderInterface
    {
        $certificateType = $certificate->getCertificateType();
        if (!$certificateType) {
            throw new LogsException($this->vpnLogManager->createLogCritical('log.pkiProviders.certificateTypeNotSet', certificate: $certificate));
        }

        // TODO in future expand this condition for other PKI protocols
        if (!$this->configurationManager->isScepForCertificateTypeAvailable($certificateType)) {
            throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.invalidPkiConfiguration', certificate: $certificate));
        }

        $pkiType = $certificateType->getPkiType();
        switch ($pkiType) {
            case PkiType::SCEP:
                if (!$certificateType->getScepUrl()) {
                    throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.scep.missingScepUrl', certificate: $certificate));
                }

                if (!$certificateType->getScepCrlUrl()) {
                    throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.scep.missingScepCrlUrl', certificate: $certificate));
                }

                if (!$certificateType->getScepRevocationUrl()) {
                    throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.scep.missingScepRevocationUrl', certificate: $certificate));
                }

                return new ScepPkiProvider(
                    $this->projectDir,
                    $this->getCertificateRequestDir(),
                    $this->fileManager,
                    $certificateType->getScepUrl(),
                    $certificateType->getScepCrlUrl(),
                    $certificateType->getScepRevocationUrl(),
                    $this->httpClient,
                    $certificateType->getScepTimeout(),
                    $certificateType->getScepVerifyServerSslCertificate(),
                    $certificateType->getScepRevocationBasicAuthUser(),
                    $certificateType->getScepRevocationBasicAuthPassword(),
                );
            case PkiType::NONE:
            default:
                throw new \Exception('Unsupported PKI protocol type "'.$pkiType->value.'"');
        }
    }
}
