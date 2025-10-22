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

namespace App\Model;

use App\Enum\MicrosoftOidcCredential;
use App\Enum\SingleSignOn;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Important! This data is serialized and available for anonymous users.
 */
class PublicConfiguration
{
    /**
     * Is maintenance mode enabled?
     */
    #[Groups(['public:configuration'])]
    private bool $maintenanceMode = false;

    /**
     * Is PHP config generator enabled?
     */
    #[Groups(['public:configuration'])]
    private bool $configGeneratorPhp = false;

    /**
     * Is Twig config generator enabled?
     */
    #[Groups(['public:configuration'])]
    private bool $configGeneratorTwig = false;

    /**
     * Is SCEP available?
     */
    #[Groups(['public:configuration'])]
    private bool $isScepAvailable = false;

    /**
     * Is VPN available?
     */
    #[Groups(['public:configuration'])]
    private bool $isVpnAvailable = false;

    /**
     * Is TOTP enabled?
     */
    #[Groups(['public:configuration'])]
    private bool $isTotpEnabled = false;

    /**
     * Is Radius enabled?
     */
    #[Groups(['public:configuration'])]
    private bool $isRadiusEnabled = false;

    /**
     * Disable REST API documentation for Administrator.
     */
    #[Groups(['public:configuration'])]
    private bool $disableAdminRestApiDocumentation = false;

    /**
     * Disable REST API documentation for user with device management permissions.
     */
    #[Groups(['public:configuration'])]
    private bool $disableSmartemsRestApiDocumentation = false;

    /**
     * Disable REST API documentation for user with VPN permissions.
     */
    #[Groups(['public:configuration'])]
    private bool $disableVpnSecuritySuiteRestApiDocumentation = false;

    /**
     * Number of old passwords that will be blocked from reusing. Set to 0 to disable.
     */
    #[Groups(['public:configuration'])]
    private ?int $passwordBlockReuseOldPasswordCount = 0;

    /**
     * Password minimum length.
     */
    #[Groups(['public:configuration'])]
    private ?int $passwordMinimumLength = 8;

    /**
     * Is password required to contain a digit?
     */
    #[Groups(['public:configuration'])]
    private ?bool $passwordDigitRequired = false;

    /**
     * Is password required to contain a big and small character?
     */
    #[Groups(['public:configuration'])]
    private ?bool $passwordBigSmallCharRequired = false;

    /**
     * Is password required to contain a special character?
     */
    #[Groups(['public:configuration'])]
    private ?bool $passwordSpecialCharRequired = false;

    /**
     * Is TOTP secret generated for one or more users?
     */
    #[Groups(['public:configuration'])]
    private bool $isTotpSecretGenerated = false;

    /**
     * Single sign-on (SSO).
     */
    #[Groups(['public:configuration'])]
    private ?SingleSignOn $singleSignOn = SingleSignOn::DISABLED;

    /**
     * Microsoft credential.
     */
    #[Groups(['public:configuration'])]
    private ?MicrosoftOidcCredential $microsoftOidcCredential = null;

    /**
     * Microsoft public key valid to for uploaded certificate.
     */
    #[Groups(['public:configuration'])]
    private ?\DateTime $microsoftOidcUploadedCertificatePublicValidTo = null;

    /**
     * Microsoft public key valid to for generated certificate.
     */
    #[Groups(['public:configuration'])]
    private ?\DateTime $microsoftOidcGeneratedCertificatePublicValidTo = null;

    public function isIsTotpSecretGenerated(): bool
    {
        return $this->isTotpSecretGenerated;
    }

    public function setIsTotpSecretGenerated(bool $isTotpSecretGenerated)
    {
        $this->isTotpSecretGenerated = $isTotpSecretGenerated;
    }

    public function getPasswordBlockReuseOldPasswordCount(): ?int
    {
        return $this->passwordBlockReuseOldPasswordCount;
    }

    public function setPasswordBlockReuseOldPasswordCount(?int $passwordBlockReuseOldPasswordCount)
    {
        $this->passwordBlockReuseOldPasswordCount = $passwordBlockReuseOldPasswordCount;
    }

    public function getPasswordMinimumLength(): ?int
    {
        return $this->passwordMinimumLength;
    }

    public function setPasswordMinimumLength(?int $passwordMinimumLength)
    {
        $this->passwordMinimumLength = $passwordMinimumLength;
    }

    public function getPasswordDigitRequired(): ?bool
    {
        return $this->passwordDigitRequired;
    }

    public function setPasswordDigitRequired(?bool $passwordDigitRequired)
    {
        $this->passwordDigitRequired = $passwordDigitRequired;
    }

    public function getPasswordBigSmallCharRequired(): ?bool
    {
        return $this->passwordBigSmallCharRequired;
    }

    public function setPasswordBigSmallCharRequired(?bool $passwordBigSmallCharRequired)
    {
        $this->passwordBigSmallCharRequired = $passwordBigSmallCharRequired;
    }

    public function getPasswordSpecialCharRequired(): ?bool
    {
        return $this->passwordSpecialCharRequired;
    }

    public function setPasswordSpecialCharRequired(?bool $passwordSpecialCharRequired)
    {
        $this->passwordSpecialCharRequired = $passwordSpecialCharRequired;
    }

    public function getIsTotpEnabled(): bool
    {
        return $this->isTotpEnabled;
    }

    public function setIsTotpEnabled(bool $isTotpEnabled)
    {
        $this->isTotpEnabled = $isTotpEnabled;
    }

    public function getMaintenanceMode(): bool
    {
        return $this->maintenanceMode;
    }

    public function setMaintenanceMode(bool $maintenanceMode)
    {
        $this->maintenanceMode = $maintenanceMode;
    }

    public function isConfigGeneratorPhp(): bool
    {
        return $this->configGeneratorPhp;
    }

    public function setConfigGeneratorPhp(bool $configGeneratorPhp)
    {
        $this->configGeneratorPhp = $configGeneratorPhp;
    }

    public function isConfigGeneratorTwig(): bool
    {
        return $this->configGeneratorTwig;
    }

    public function setConfigGeneratorTwig(bool $configGeneratorTwig)
    {
        $this->configGeneratorTwig = $configGeneratorTwig;
    }

    public function getIsVpnAvailable(): bool
    {
        return $this->isVpnAvailable;
    }

    public function setIsVpnAvailable(bool $isVpnAvailable)
    {
        $this->isVpnAvailable = $isVpnAvailable;
    }

    public function getIsRadiusEnabled(): bool
    {
        return $this->isRadiusEnabled;
    }

    public function setIsRadiusEnabled(bool $isRadiusEnabled)
    {
        $this->isRadiusEnabled = $isRadiusEnabled;
    }

    public function isIsScepAvailable(): bool
    {
        return $this->isScepAvailable;
    }

    public function setIsScepAvailable(bool $isScepAvailable)
    {
        $this->isScepAvailable = $isScepAvailable;
    }

    public function getDisableAdminRestApiDocumentation(): bool
    {
        return $this->disableAdminRestApiDocumentation;
    }

    public function setDisableAdminRestApiDocumentation(bool $disableAdminRestApiDocumentation)
    {
        $this->disableAdminRestApiDocumentation = $disableAdminRestApiDocumentation;
    }

    public function getDisableSmartemsRestApiDocumentation(): bool
    {
        return $this->disableSmartemsRestApiDocumentation;
    }

    public function setDisableSmartemsRestApiDocumentation(bool $disableSmartemsRestApiDocumentation)
    {
        $this->disableSmartemsRestApiDocumentation = $disableSmartemsRestApiDocumentation;
    }

    public function getDisableVpnSecuritySuiteRestApiDocumentation(): bool
    {
        return $this->disableVpnSecuritySuiteRestApiDocumentation;
    }

    public function setDisableVpnSecuritySuiteRestApiDocumentation(bool $disableVpnSecuritySuiteRestApiDocumentation)
    {
        $this->disableVpnSecuritySuiteRestApiDocumentation = $disableVpnSecuritySuiteRestApiDocumentation;
    }

    public function getSingleSignOn(): ?SingleSignOn
    {
        return $this->singleSignOn;
    }

    public function setSingleSignOn(?SingleSignOn $singleSignOn)
    {
        $this->singleSignOn = $singleSignOn;
    }

    public function getMicrosoftOidcCredential(): ?MicrosoftOidcCredential
    {
        return $this->microsoftOidcCredential;
    }

    public function setMicrosoftOidcCredential(?MicrosoftOidcCredential $microsoftOidcCredential)
    {
        $this->microsoftOidcCredential = $microsoftOidcCredential;
    }

    public function getMicrosoftOidcUploadedCertificatePublicValidTo(): ?\DateTime
    {
        return $this->microsoftOidcUploadedCertificatePublicValidTo;
    }

    public function setMicrosoftOidcUploadedCertificatePublicValidTo(?\DateTime $microsoftOidcUploadedCertificatePublicValidTo)
    {
        $this->microsoftOidcUploadedCertificatePublicValidTo = $microsoftOidcUploadedCertificatePublicValidTo;
    }

    public function getMicrosoftOidcGeneratedCertificatePublicValidTo(): ?\DateTime
    {
        return $this->microsoftOidcGeneratedCertificatePublicValidTo;
    }

    public function setMicrosoftOidcGeneratedCertificatePublicValidTo(?\DateTime $microsoftOidcGeneratedCertificatePublicValidTo)
    {
        $this->microsoftOidcGeneratedCertificatePublicValidTo = $microsoftOidcGeneratedCertificatePublicValidTo;
    }
}
