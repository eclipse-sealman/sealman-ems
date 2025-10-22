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

use App\Entity\CertificateType;
use App\Entity\Config;
use App\Entity\Configuration;
use App\Enum\ConfigGenerator;
use App\Enum\PkiType;
use App\Enum\RouterIdentifier;
use App\Model\PublicConfiguration;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\FeatureManagerTrait;
use App\Service\Helper\TotpManagerTrait;
use App\Service\Helper\UserTrait;

class ConfigurationManager
{
    use EntityManagerTrait;
    use UserTrait;
    use TotpManagerTrait;
    use FeatureManagerTrait;

    /**
     * @var ?Configuration
     */
    protected $configuration = null;

    public function refreshConfiguration()
    {
        $this->configuration = null;
        $this->getConfiguration();
    }

    // Cache'ing function do not touch, make changes in getConfig or write get[key] or is[key] or has[key] function to manipulate data loading/processing e.g. getMinRsrp()
    public function getConfiguration()
    {
        if (null === $this->configuration) {
            $configuration = $this->entityManager->getRepository(Configuration::class)->findOneBy([]);

            $this->configuration = $configuration;
        }

        return $this->configuration;
    }

    public function getPublicConfiguration(): PublicConfiguration
    {
        $configuration = $this->getConfiguration();
        $publicConfiguration = new PublicConfiguration();

        $publicConfiguration->setMaintenanceMode($this->isMaintenanceModeEnabled());
        $publicConfiguration->setConfigGeneratorPhp($configuration->getConfigGeneratorPhp());
        $publicConfiguration->setConfigGeneratorTwig($configuration->getConfigGeneratorTwig());

        $publicConfiguration->setPasswordBlockReuseOldPasswordCount($configuration->getPasswordBlockReuseOldPasswordCount());
        $publicConfiguration->setPasswordMinimumLength($configuration->getPasswordMinimumLength());
        $publicConfiguration->setPasswordDigitRequired($configuration->getPasswordDigitRequired());
        $publicConfiguration->setPasswordBigSmallCharRequired($configuration->getPasswordBigSmallCharRequired());
        $publicConfiguration->setPasswordSpecialCharRequired($configuration->getPasswordSpecialCharRequired());

        $publicConfiguration->setIsTotpEnabled($configuration->getTotpEnabled());
        $publicConfiguration->setIsRadiusEnabled($configuration->getRadiusEnabled());

        $publicConfiguration->setDisableAdminRestApiDocumentation($configuration->getDisableAdminRestApiDocumentation());
        $publicConfiguration->setDisableSmartemsRestApiDocumentation($configuration->getDisableSmartemsRestApiDocumentation());
        $publicConfiguration->setDisableVpnSecuritySuiteRestApiDocumentation($configuration->getDisableVpnSecuritySuiteRestApiDocumentation());

        $isScepAvailable = $this->featureManager->isScepAvailable();
        $publicConfiguration->setIsScepAvailable($isScepAvailable);

        $isVpnAvailable = $this->featureManager->isVpnAvailable();
        $publicConfiguration->setIsVpnAvailable($isVpnAvailable);

        $publicConfiguration->setIsTotpSecretGenerated($this->totpManager->isTotpSecretGenerated());

        $publicConfiguration->setSingleSignOn($configuration->getSingleSignOn());
        $publicConfiguration->setMicrosoftOidcCredential($configuration->getMicrosoftOidcCredential());
        $publicConfiguration->setMicrosoftOidcUploadedCertificatePublicValidTo($configuration->getMicrosoftOidcUploadedCertificatePublicValidTo());
        $publicConfiguration->setMicrosoftOidcGeneratedCertificatePublicValidTo($configuration->getMicrosoftOidcGeneratedCertificatePublicValidTo());

        return $publicConfiguration;
    }

    /**
     * Check if PHP config generator is used.
     */
    public function isConfigGeneratorPhpUsed(): bool
    {
        return $this->isConfigGeneratorUsed(ConfigGenerator::PHP);
    }

    /**
     * Check if Twig config generator is used.
     */
    public function isConfigGeneratorTwigUsed(): bool
    {
        return $this->isConfigGeneratorUsed(ConfigGenerator::TWIG);
    }

    /**
     * Check if specified config generator is used.
     */
    public function isConfigGeneratorUsed(ConfigGenerator $generator): bool
    {
        $configCount = $this->getRepository(Config::class)->count([
            'generator' => $generator,
        ]);

        return $configCount > 0 ? true : false;
    }

    public function isVpnSecuritySuiteBlocked(): bool
    {
        return !$this->featureManager->isVpnAvailable();
    }

    public function isVpnSecuritySuiteAvailable(): bool
    {
        return !$this->isVpnSecuritySuiteBlocked() && $this->getConfiguration()->getOpnsenseUrl() && $this->getConfiguration()->getOpnsenseTimeout() > 0;
    }

    public function isScepBlocked(): bool
    {
        return !$this->featureManager->isScepAvailable();
    }

    public function isScepForCertificateTypeAvailable(CertificateType $certificateType): bool
    {
        return !$this->isScepBlocked() && PkiType::SCEP == $certificateType->getPkiType() && $certificateType->getScepUrl() && $certificateType->getScepCrlUrl() && $certificateType->getScepRevocationUrl() && $certificateType->getScepHashFunction() && $certificateType->getScepKeyLength() && $certificateType->getScepTimeout() > 0;
    }

    public function isMaintenanceModeEnabled(): bool
    {
        return $this->getConfiguration()->getMaintenanceMode();
    }

    public function isConnectedToOpenVPN(): bool
    {
        return $this->getUser()->getVpnConnected();
    }

    public function getSessionTime(): int
    {
        return $this->sessionTimeToLive;
    }

    public function getRouterIdentifier(): ?RouterIdentifier
    {
        return $this->getConfiguration()->getRouterIdentifier();
    }

    public function isSerialRouterIdentifier(): bool
    {
        return RouterIdentifier::SERIAL == $this->getRouterIdentifier();
    }

    public function isImsiRouterIdentifier(): bool
    {
        return RouterIdentifier::IMSI == $this->getRouterIdentifier();
    }
}
