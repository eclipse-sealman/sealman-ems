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
use App\Model\CertificateUploadFilesModel;
use App\Model\CertificateUploadPkcs12Model;
use App\Service\Helper\EncryptionManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\EventDispatcherTrait;
use App\Service\Helper\PkiProvidersManagerTrait;
use App\Service\Helper\VpnLogManagerTrait;
use App\Service\Helper\VpnManagerTrait;

class CertificateManager
{
    use EntityManagerTrait;
    use EncryptionManagerTrait;
    use VpnLogManagerTrait;
    use VpnManagerTrait;
    use PkiProvidersManagerTrait;
    use EventDispatcherTrait;

    public function revokeCertificate(Certificate $certificateObject): void
    {
        $this->dispatchCertificatePreRevoke($certificateObject);
        $this->pkiProvidersManager->revokeCertificate($certificateObject);
        $this->dispatchCertificatePostRevoke($certificateObject);
    }

    public function generateCertificate(Certificate $certificateObject): void
    {
        $this->dispatchCertificatePreGenerate($certificateObject);
        $this->pkiProvidersManager->generateCertificate($certificateObject);
        $this->dispatchCertificatePostGenerate($certificateObject);
    }

    /*
    Validates PKCS12 model
    Returns true on success
    Returns violation string (error string) on failure
    */
    public function validatePkcs12Model(CertificateUploadPkcs12Model $model): bool|string
    {
        if (!$model->getPkcs12()) {
            return 'validation.required';
        }

        if (!openssl_pkcs12_read($this->getUploadedFileContent($model->getPkcs12()), $certInfo, $model->getPassword() ?: '')) {
            return 'validation.certificate.incorrectPassword';
        }

        if (!isset($certInfo['cert']) || !$certInfo['cert']) {
            return 'validation.certificate.noCertificateProvided';
        }

        $certificateData = openssl_x509_parse($certInfo['cert']);

        if (!isset($certificateData['subject']) || !isset($certificateData['subject']['CN'])) {
            return 'validation.certificate.noCertificateSubject';
        }

        $commonName = $certificateData['subject']['CN'];

        if (!$this->validateCommonNameUniqueness($commonName, $model->getCertificateObject())) {
            return 'validation.certificate.commonNameTaken';
        }

        return true;
    }

    /*
    Validates files model
    Returns true on success
    Returns violation string (error string) on failure
    */
    public function validateFilesModelCertificate(CertificateUploadFilesModel $model): bool|string
    {
        if (!$model->getCertificate()) {
            return true;
        }

        $certificateData = openssl_x509_parse($this->getUploadedFileContent($model->getCertificate()));

        if (!isset($certificateData['subject']) || !isset($certificateData['subject']['CN'])) {
            return 'validation.certificate.invalidCertificate';
        }

        $commonName = $certificateData['subject']['CN'];

        if (!$this->validateCommonNameUniqueness($commonName, $model->getCertificateObject())) {
            return 'validation.certificate.commonNameTaken';
        }

        return true;
    }

    public function validateCommonNameUniqueness(string $commonName, ?Certificate $certificateObject = null): bool
    {
        $queryBuilder = $this->getRepository(Certificate::class)->createQueryBuilder('d');
        $queryBuilder->select('COUNT(d.id)');
        $queryBuilder->andWhere('d.certificateSubject = :certificateSubject');
        $queryBuilder->setParameter('certificateSubject', $commonName);

        if ($certificateObject && $certificateObject->getId()) {
            $queryBuilder->andWhere('d.id != :id');
            $queryBuilder->setParameter('id', $certificateObject->getId());
        }

        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        return $count > 0 ? false : true;
    }

    protected function getUploadedFileContent(string $token): string|false
    {
        $fileArray = UploadManager::getTusFile($token);
        $filepath = $fileArray['file_path'];
        $content = file_get_contents($filepath);

        return $content;
    }

    public function handleUploadCertificatePkcs12(CertificateUploadPkcs12Model $model): bool|string
    {
        $certificateObject = $model->getCertificateObject();

        openssl_pkcs12_read($this->getUploadedFileContent($model->getPkcs12()), $certInfo, $model->getPassword() ?: '');

        $certificateObject->setCertificateGenerated(false);
        $certificateObject->setCertificate($this->encryptionManager->encrypt($certInfo['cert']));
        $certificateObject->setPrivateKey($this->encryptionManager->encrypt($certInfo['pkey']));

        if ($model->getPassword()) {
            $certificateObject->setPkcsPrivateKeyPassword($this->encryptionManager->encrypt($model->getPassword()));
        } else {
            $certificateObject->setPkcsPrivateKeyPassword(null);
        }

        $certificateData = openssl_x509_parse($certInfo['cert']);

        if (!isset($certificateData['validTo_time_t']) || !isset($certificateData['subject']) || !isset($certificateData['subject']['CN'])) {
            return 'validation.certificate.noCertificateSubject';
        }

        $validTo = new \DateTime();
        $validTo->setTimestamp($certificateData['validTo_time_t']);

        $certificateObject->setCertificateValidTo($validTo);
        $certificateObject->setCertificateSubject($certificateData['subject']['CN']);

        if (isset($certInfo['extracerts'][0])) {
            $certChain = false;
            foreach ($certInfo['extracerts'] as $cert) {
                $certificateData = openssl_x509_parse($cert);
                if (isset($certificateData['subject']['CN'])) {
                    if (!$certChain) {
                        $certChain .= "\n";
                    }
                    $certChain .= $cert;
                }
            }
            $certificateObject->setCertificateCa($this->encryptionManager->encrypt($certChain));
            $certificateData = openssl_x509_parse($certInfo['extracerts'][0]);
            $certificateObject->setCertificateCaSubject($certificateData['subject']['CN']);
        }

        $this->vpnLogManager->createLogInfo('log.certificate.pkcs12Uploaded', certificate: $certificateObject);

        $this->entityManager->persist($certificateObject);
        $this->entityManager->flush();

        return true;
    }

    public function handleUploadCertificateFiles(CertificateUploadFilesModel $model): bool|string
    {
        $certificateObject = $model->getCertificateObject();

        if ($model->getCertificate()) {
            $certificateObject->setCertificate($this->encryptionManager->encrypt($this->getUploadedFileContent($model->getCertificate())));
            $certificateData = openssl_x509_parse($this->getUploadedFileContent($model->getCertificate()));

            if (!isset($certificateData['validTo_time_t']) || !isset($certificateData['subject']) || !isset($certificateData['subject']['CN'])) {
                return 'validation.certificate.noCertificateSubject';
            }

            $validTo = new \DateTime();
            $validTo->setTimestamp($certificateData['validTo_time_t']);

            $certificateObject->setCertificateValidTo($validTo);
            $certificateObject->setCertificateSubject($certificateData['subject']['CN']);

            $this->vpnLogManager->createLogInfo('log.certificate.publicUploaded', certificate: $certificateObject);
        }

        if ($model->getPrivateKey()) {
            $certificateObject->setPrivateKey($this->encryptionManager->encrypt($this->getUploadedFileContent($model->getPrivateKey())));

            $this->vpnLogManager->createLogInfo('log.certificate.privateUploaded', certificate: $certificateObject);
        }

        if ($model->getCertificateCa()) {
            $certificateObject->setCertificateCa($this->encryptionManager->encrypt($this->getUploadedFileContent($model->getCertificateCa())));
            $certificateData = openssl_x509_parse($this->getUploadedFileContent($model->getCertificateCa()));
            $certificateObject->setCertificateCaSubject($certificateData['subject']['CN']);

            $this->vpnLogManager->createLogInfo('log.certificate.caUploaded', certificate: $certificateObject);
        }

        $certificateObject->setPkcsPrivateKeyPassword(null);

        $this->entityManager->persist($certificateObject);
        $this->entityManager->flush();

        return true;
    }

    public function handleDeleteCertificate(Certificate $certificateObject): void
    {
        $certificateObject->setCertificateSubject(null);
        $certificateObject->setCertificateCaSubject(null);
        $certificateObject->setCertificate(null);
        $certificateObject->setCertificateCa(null);
        $certificateObject->setPrivateKey(null);
        $certificateObject->setPkcsPrivateKeyPassword(null);
        $certificateObject->setCertificateValidTo(null);
        $certificateObject->setCertificateGenerated(false);

        $this->entityManager->persist($certificateObject);
        $this->entityManager->flush();

        $this->vpnLogManager->createLogInfo('log.certificate.deletedSuccess', certificate: $certificateObject);
    }

    public function getCertificateCa(Certificate $certificateObject): ?string
    {
        $certificate = null;

        if ($certificateObject->getCertificateCa()) {
            $certificate = $this->encryptionManager->decrypt($certificateObject->getCertificateCa());
        }

        return $certificate;
    }

    public function getCertificate(Certificate $certificateObject): ?string
    {
        $certificate = null;

        if ($certificateObject->getCertificate()) {
            $certificate = $this->encryptionManager->decrypt($certificateObject->getCertificate());
        }

        return $certificate;
    }

    public function getPrivateKey(Certificate $certificateObject): ?string
    {
        $privateKey = null;

        if ($certificateObject->getPrivateKey()) {
            $privateKey = $this->encryptionManager->decrypt($certificateObject->getPrivateKey());
        }

        return $privateKey;
    }

    public function getPkcsPrivateKeyPassword(Certificate $certificateObject): ?string
    {
        $pkcsPrivateKeyPassword = null;

        if ($certificateObject->getPkcsPrivateKeyPassword()) {
            $pkcsPrivateKeyPassword = $this->encryptionManager->decrypt($certificateObject->getPkcsPrivateKeyPassword());
        }

        return $pkcsPrivateKeyPassword;
    }

    public function getPkcs12(Certificate $certificateObject): ?string
    {
        $pkcs12 = null;

        $args = [];

        if ($certificateObject->getCertificateCa()) {
            $chain = $this->getCertificateCa($certificateObject);
            $chainCerts = explode('-----END CERTIFICATE-----', $chain);
            foreach ($chainCerts as $cert) {
                $args['extracerts'][] = $cert.'-----END CERTIFICATE-----';
            }
        }

        if ($certificateObject->getCertificate() && $certificateObject->getPrivateKey()) {
            openssl_pkcs12_export($this->getCertificate($certificateObject), $pkcs12, $this->getPrivateKey($certificateObject), $certificateObject->getPkcsPrivateKeyPassword() ? $this->encryptionManager->decrypt($certificateObject->getPkcsPrivateKeyPassword()) : '', $args);
        }

        return $pkcs12;
    }
}
