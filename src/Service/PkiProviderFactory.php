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
use App\Enum\PkiType;
use App\Exception\LogsException;
use App\Provider\Interface\PkiProviderInterface;
use App\Provider\ScepPkiProvider;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\HttpClientTrait;
use App\Service\Helper\OpenSslManagerTrait;
use App\Service\Helper\SymfonyDirTrait;
use App\Service\Helper\VpnLogManagerTrait;

class PkiProviderFactory
{
    use VpnLogManagerTrait;
    use ConfigurationManagerTrait;
    use SymfonyDirTrait;
    use HttpClientTrait;
    use OpenSslManagerTrait;

    // Using Certificate as parameter instead of CertificateType for better logs
    public function getProvider(?Certificate $certificate = null, ?CertificateType $certificateType = null): PkiProviderInterface
    {
        if (null === $certificate && null === $certificateType) {
            throw new \LogsException('Either $certificate or $certificateType must be provided.');
        }

        if (null === $certificateType) {
            $certificateType = $certificate->getCertificateType();
            if (!$certificateType) {
                throw new LogsException($this->vpnLogManager->createLogCritical('log.pkiProviders.certificateTypeNotSet', certificate: $certificate));
            }
        }

        // TODO in future expand this condition for other PKI protocols
        if (!$this->configurationManager->isScepForCertificateTypeAvailable($certificateType)) {
            throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.invalidPkiConfiguration', certificate: $certificate));
        }

        $pkiType = $certificateType->getPkiType();
        switch ($pkiType) {
            case PkiType::SCEP:
                if (!$certificateType->getScepUrl()) {
                    throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.scep.missingScepUrl', ['certificateType' => $certificateType->getRepresentation()], certificate: $certificate));
                }

                if (!$certificateType->getScepCrlUrl()) {
                    throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.scep.missingScepCrlUrl', ['certificateType' => $certificateType->getRepresentation()], certificate: $certificate));
                }

                if (!$certificateType->getScepRevocationUrl()) {
                    throw new LogsException($this->vpnLogManager->createLogError('log.pkiProviders.scep.missingScepRevocationUrl', ['certificateType' => $certificateType->getRepresentation()], certificate: $certificate));
                }

                return new ScepPkiProvider(
                    $this->projectDir,
                    $this->getCertificateRequestDir(),
                    $this->openSslManager,
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

    protected function getCertificateRequestDir(): string
    {
        return '/private/certificate_request/';
    }
}
