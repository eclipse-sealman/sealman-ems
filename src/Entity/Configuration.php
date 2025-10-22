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

namespace App\Entity;

use App\Entity\Traits\BlameableEntityInterface;
use App\Entity\Traits\BlameableEntityTrait;
use App\Entity\Traits\TimestampableEntityInterface;
use App\Entity\Traits\TimestampableEntityTrait;
use App\Entity\Traits\UploadTrait;
use App\Enum\MicrosoftOidcCredential;
use App\Enum\RadiusAuthenticationProtocol;
use App\Enum\RouterIdentifier;
use App\Enum\SingleSignOn;
use App\Enum\TotpAlgorithm;
use App\Enum\TotpWindow;
use App\Model\AuditableInterface;
use App\Model\UploadInterface;
use App\Validator\Constraints\ConfigurationGeneral;
use App\Validator\Constraints\ConfigurationSso;
use App\Validator\Constraints\ConfigurationVpnSubnet;
use App\Validator\Constraints\TusPrivateKey;
use App\Validator\Constraints\TusX509;
use App\Validator\Constraints\TusX509CheckPrivateKey;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity]
#[ConfigurationGeneral(groups: ['configuration:general'])]
#[ConfigurationSso(groups: ['configuration:sso'])]
#[ConfigurationVpnSubnet(groups: ['configuration:vpn'])]
class Configuration implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, UploadInterface, AuditableInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;
    use UploadTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Is maintenance mode enabled?
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $maintenanceMode = false;

    /**
     * Router identifier.
     */
    #[Groups(['configuration:general', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:general'])]
    #[ORM\Column(type: Types::STRING, enumType: RouterIdentifier::class)]
    private ?RouterIdentifier $routerIdentifier = RouterIdentifier::SERIAL;

    /**
     * Is PHP config generator enabled?
     */
    #[Groups(['configuration:general', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $configGeneratorPhp = false;

    /**
     * Is Twig config generator enabled?
     */
    #[Groups(['configuration:general', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $configGeneratorTwig = true;

    /**
     * Days until user password is considered expired and user is required to change it. Set to 0 to disable.
     */
    #[Groups(['configuration:general', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['configuration:general'])]
    #[Assert\NotBlank(groups: ['configuration:general'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $passwordExpireDays = 0;

    /**
     * Number of old passwords that will be blocked from reusing. Set to 0 to disable.
     */
    #[Groups(['configuration:general', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['configuration:general'])]
    #[Assert\NotBlank(groups: ['configuration:general'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $passwordBlockReuseOldPasswordCount = 0;

    /**
     * Password minimum length.
     */
    #[Groups(['configuration:general', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 1, groups: ['configuration:general'])]
    #[Assert\NotBlank(groups: ['configuration:general'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $passwordMinimumLength = 8;

    /**
     * Is password required to contain a digit?
     */
    #[Groups(['configuration:general', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $passwordDigitRequired = false;

    /**
     * Is password required to contain a big and small character?
     */
    #[Groups(['configuration:general', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $passwordBigSmallCharRequired = false;

    /**
     * Is password required to contain a special character?
     */
    #[Groups(['configuration:general', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $passwordSpecialCharRequired = false;

    /**
     * Auto remove backups after specified number of days. Set to 0 to disable.
     */
    #[Groups(['configuration:general', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['configuration:general'])]
    #[Assert\NotBlank(groups: ['configuration:general'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $autoRemoveBackupsAfter = 0;

    /**
     * Disk usage alarm (in percent).
     */
    #[Groups(['configuration:general', AuditableInterface::GROUP])]
    #[Assert\Range(min: 0, max: 100, groups: ['configuration:general'])]
    #[Assert\NotBlank(groups: ['configuration:general'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $diskUsageAlarm = 15;

    /**
     * Is password required to contain a special character?
     */
    #[Groups(['configuration:general', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $failedLoginAttemptsEnabled = true;

    /**
     * Amount of consecutive failed login attempts after account is disabled.
     */
    #[Groups(['configuration:general', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'failedLoginAttemptsEnabled', groups: ['configuration:general'])]
    #[Assert\GreaterThanOrEqual(value: 1, groups: ['configuration:general'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $failedLoginAttemptsLimit = 3;

    /**
     * Account disabled duration after failed login attempts limit is exceeded.
     */
    #[Groups(['configuration:general', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'failedLoginAttemptsEnabled', groups: ['configuration:general'])]
    #[Assert\DateModifier(groups: ['configuration:general'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $failedLoginAttemptsDisablingDuration = '+2 hours';

    /**
     * Is TOTP enabled?
     */
    #[Groups(['configuration:totp', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $totpEnabled = false;

    /**
     * TOTP token length.
     */
    #[Groups(['configuration:totp', AuditableInterface::GROUP])]
    #[Assert\Range(min: 4, max: 16, groups: ['configuration:totp'])]
    #[Assert\NotBlankOnTrue(propertyPath: 'totpEnabled', groups: ['configuration:totp'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $totpTokenLength = 6;

    /**
     * TOTP secret length. Supported options: 16, 32, 64 or 128.
     */
    #[Groups(['configuration:totp', AuditableInterface::GROUP])]
    #[Assert\Choice(choices: [16, 32, 64, 128], groups: ['configuration:totp'])]
    #[Assert\NotBlankOnTrue(propertyPath: 'totpEnabled', groups: ['configuration:totp'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $totpSecretLength = 32;

    /**
     * TOTP key regeneration interval in seconds.
     */
    #[Groups(['configuration:totp', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 1, groups: ['configuration:totp'])]
    #[Assert\NotBlankOnTrue(propertyPath: 'totpEnabled', groups: ['configuration:totp'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $totpKeyRegeneration = 30;

    /**
     * TOTP hash algorithm. Supported options: sha1, sha256 or sha512.
     */
    #[Groups(['configuration:totp', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'totpEnabled', groups: ['configuration:totp'])]
    #[ORM\Column(type: Types::STRING, enumType: TotpAlgorithm::class, nullable: true)]
    private ?TotpAlgorithm $totpAlgorithm = TotpAlgorithm::SHA1;

    /**
     * TOTP validity window.
     */
    #[Groups(['configuration:totp', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'totpEnabled', groups: ['configuration:totp'])]
    #[ORM\Column(type: Types::STRING, enumType: TotpWindow::class, nullable: true)]
    private ?TotpWindow $totpWindow = TotpWindow::INTERVAL_1;

    /**
     * Is VPN connection time limited?
     */
    #[Groups(['configuration:vpn', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $vpnConnectionLimit = false;

    /**
     * VPN connection duration if connectionLimit is enabled.
     * TODO REST API Documentation $vpnConnectionDuration is mapped as required due to lack of nullable: true. Why this is not nullable: true?
     */
    #[Groups(['configuration:vpn', AuditableInterface::GROUP])]
    #[Assert\DateModifier(groups: ['configuration:vpn'])]
    #[Assert\NotBlankOnTrue(propertyPath: 'vpnConnectionLimit', groups: ['configuration:vpn'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $vpnConnectionDuration = '+4 hours';

    /**
     * OpnSense API Url.
     */
    #[Groups(['configuration:vpn', AuditableInterface::GROUP])]
    #[Assert\Url(groups: ['configuration:vpn'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $opnsenseUrl = null;

    /**
     * OpnSense API key.
     */
    #[Groups(['configuration:vpn', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $opnsenseApiKey = null;

    /**
     * OpnSense API secret.
     */
    #[Groups(['configuration:vpn', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $opnsenseApiSecret = null;

    /**
     * OpnSense timeout in seconds.
     */
    #[Groups(['configuration:vpn', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 1, groups: ['configuration:vpn'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $opnsenseTimeout = 5;

    /**
     * Should OpnSense SSL certificate be verified.
     */
    #[Groups(['configuration:vpn', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $verifyOpnsenseSslCertificate = false;

    /**
     * Technicians Openvpn server description.
     */
    #[Groups(['configuration:vpn', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $techniciansOpenvpnServerDescription = 'Technicians OpenVPN';

    /**
     * Devices Openvpn server description.
     */
    #[Groups(['configuration:vpn', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $devicesOpenvpnServerDescription = 'Devices OpenVPN';

    /**
     * Technicians Openvpn server index.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $techniciansOpenvpnServerIndex = null;

    /**
     * Devices Openvpn server index.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $devicesOpenvpnServerIndex = null;

    /**
     * Devices VPN network - only one can be set e.g. 172.16.0.0/16.
     */
    #[Groups(['configuration:vpn', 'options:masqueradeDefaultSubnets', AuditableInterface::GROUP])]
    // All validation in done on Entity level by ConfigurationVpnSubnet validator
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $devicesVpnNetworks = '172.16.0.0/16';

    /**
     * Devices VPN network range e.g. 172.16.0.2-172.16.0.250.
     */
    #[Groups(['configuration:vpn', AuditableInterface::GROUP])]
    // All validation in done on Entity level by ConfigurationVpnSubnet validator
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $devicesVpnNetworksRanges = '172.16.0.2-172.16.255.253';

    /**
     * Devices VIRTUAL VPN networks - list - multiple can be added e.g. 172.16.0.0/16,192.168.2.0/24.
     */
    #[Groups(['configuration:vpn', AuditableInterface::GROUP])]
    // All validation in done on Entity level by ConfigurationVpnSubnet validator
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $devicesVirtualVpnNetworks = '10.0.0.0/14';

    /**
     * Devices VIRTUAL VPN networks ranges - list - multiple can be added e.g. 172.16.0.2-172.16.0.250, .
     */
    #[Groups(['configuration:vpn', AuditableInterface::GROUP])]
    // All validation in done on Entity level by ConfigurationVpnSubnet validator
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $devicesVirtualVpnNetworksRanges = '10.0.0.2-10.3.255.253';

    /**
     * Technicians VPN network - only one can be set e.g. 172.16.0.0/16.
     */
    #[Groups(['configuration:vpn', 'options:masqueradeDefaultSubnets', AuditableInterface::GROUP])]
    // All validation in done on Entity level by ConfigurationVpnSubnet validator
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $techniciansVpnNetworks = '192.168.154.0/24';

    /**
     * Technicians VPN network range e.g. 172.16.0.2-172.16.0.250.
     */
    #[Groups(['configuration:vpn', AuditableInterface::GROUP])]
    // All validation in done on Entity level by ConfigurationVpnSubnet validator
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $techniciansVpnNetworksRanges = '192.168.154.2-192.168.154.253';

    #[Groups(['configuration:vpn', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:vpn'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $devicesOvpnTemplate = 'Template is not configured';

    #[Groups(['configuration:vpn', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:vpn'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $techniciansOvpnTemplate = 'Template is not configured';

    /**
     * Audit logs cleanup duration (in days). Remove audit logs older then cleanup duration. Disable it by setting cleanup duration to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $auditLogsCleanupDuration = 0;

    /**
     * Audit logs cleanup size (in megabytes). Remove audit logs that exceed cleanup size. Disable it by setting cleanup size to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $auditLogsCleanupSize = 0;

    /**
     * Communication logs cleanup duration (in days). Remove communication logs older then cleanup duration. Disable it by setting cleanup duration to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $communicationLogsCleanupDuration = 7;

    /**
     * Communication logs cleanup size (in megabytes). Remove communication logs that exceed cleanup size. Disable it by setting cleanup size to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $communicationLogsCleanupSize = 100;

    /**
     * Diagnose logs cleanup duration (in days). Remove diagnose logs older then cleanup duration. Disable it by setting cleanup duration to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $diagnoseLogsCleanupDuration = 7;

    /**
     * Diagnose logs cleanup size (in megabytes). Remove diagnose logs that exceed cleanup size. Disable it by setting cleanup size to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $diagnoseLogsCleanupSize = 100;

    /**
     * Config logs cleanup duration (in days). Remove config logs older then cleanup duration. Disable it by setting cleanup duration to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $configLogsCleanupDuration = 7;

    /**
     * Config logs cleanup size (in megabytes). Remove config logs that exceed cleanup size. Disable it by setting cleanup size to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $configLogsCleanupSize = 100;

    /**
     * VPN logs cleanup duration (in days). Remove VPN logs older then cleanup duration. Disable it by setting cleanup duration to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $vpnLogsCleanupDuration = 7;

    /**
     * VPN logs cleanup size (in megabytes). Remove VPN logs that exceed cleanup size. Disable it by setting cleanup size to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $vpnLogsCleanupSize = 100;

    /**
     * Device failed login attempts cleanup duration (in days). Remove device failed login attempts older then cleanup duration. Disable it by setting cleanup duration to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $deviceFailedLoginAttemptsCleanupDuration = 7;

    /**
     * Device failed login attempts cleanup size (in megabytes). Remove device failed login attempts that exceed cleanup size. Disable it by setting cleanup size to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $deviceFailedLoginAttemptsCleanupSize = 100;

    /**
     * User login attempts cleanup duration (in days). Remove user login attempts older then cleanup duration. Disable it by setting cleanup duration to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $userLoginAttemptsCleanupDuration = 7;

    /**
     * User login attempts cleanup size (in megabytes). Remove user login attempts that exceed cleanup size. Disable it by setting cleanup size to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $userLoginAttemptsCleanupSize = 100;

    /**
     * Device commands cleanup duration (in days). Remove device commands older then cleanup duration. Disable it by setting cleanup duration to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $deviceCommandsCleanupDuration = 7;

    /**
     * Device commands cleanup size (in megabytes). Remove device commands that exceed cleanup size. Disable it by setting cleanup size to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $deviceCommandsCleanupSize = 100;

    /**
     * Maintenance logs cleanup duration (in days). Remove maintenance logs older then cleanup duration. Disable it by setting cleanup duration to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $maintenanceLogsCleanupDuration = 7;

    /**
     * Maintenance logs cleanup size (in megabytes). Remove maintenance logs that exceed cleanup size. Disable it by setting cleanup size to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $maintenanceLogsCleanupSize = 100;

    /**
     * Import logs cleanup duration (in days). Remove import logs older then cleanup duration. Disable it by setting cleanup duration to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $importFileRowLogsCleanupDuration = 7;

    /**
     * Import logs cleanup size (in megabytes). Remove import logs that exceed cleanup size. Disable it by setting cleanup size to 0.
     */
    #[Groups(['configuration:logs', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:logs'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['configuration:logs'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $importFileRowLogsCleanupSize = 100;

    /**
     * Disable REST API documentation for Administrator.
     */
    #[Groups(['configuration:documentation', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $disableAdminRestApiDocumentation = false;

    /**
     * Disable REST API documentation for user with device management permissions.
     */
    #[Groups(['configuration:documentation', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $disableSmartemsRestApiDocumentation = false;

    /**
     * Disable REST API documentation for user with VPN permissions.
     */
    #[Groups(['configuration:documentation', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $disableVpnSecuritySuiteRestApiDocumentation = false;

    // System and licenses related properties
    /**
     * Installation ID.
     */
    #[ORM\Column(type: Types::STRING)]
    private ?string $installationId;

    // Radius fields

    /**
     * Is radius authentication enabled?
     */
    #[Groups(['configuration:radius', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $radiusEnabled = false;

    /**
     * Radius auth protocol: pap or chap.
     * TODO REST API Documentation $radiusAuth is mapped as required due to lack of nullable: true. Why this is not nullable: true?
     */
    #[Groups(['configuration:radius', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'radiusEnabled', groups: ['configuration:radius'])]
    #[ORM\Column(type: Types::STRING, enumType: RadiusAuthenticationProtocol::class, nullable: true)]
    private ?RadiusAuthenticationProtocol $radiusAuth = RadiusAuthenticationProtocol::PAP;

    /**
     * Radius server (hostname or IP).
     */
    #[Groups(['configuration:radius', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'radiusEnabled', groups: ['configuration:radius'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $radiusServer = null;

    /**
     * Radius secret.
     */
    #[Groups(['configuration:radius', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'radiusEnabled', groups: ['configuration:radius'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $radiusSecret = null;

    /**
     * Radius NAS IP address.
     */
    #[Groups(['configuration:radius', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'radiusEnabled', groups: ['configuration:radius'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $radiusNasAddress = null;

    /**
     * Radius NAS port.
     */
    #[Groups(['configuration:radius', AuditableInterface::GROUP])]
    #[Assert\Range(min: 0, max: 65535, groups: ['configuration:radius'])]
    #[Assert\NotBlankOnTrue(propertyPath: 'radiusEnabled', groups: ['configuration:radius'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $radiusNasPort = 10;

    /**
     * Radius Welotec group mapping enabled?
     * Should User credentials be based on welotec_group_mapping (based on Welotec-Group-Name). If disabled Radius Users will always be logged as Administrators.
     */
    #[Groups(['configuration:radius', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $radiusWelotecGroupMappingEnabled = false;

    /**
     * Radius Welotec access tags mapping enabled?
     * Should User Access Tags be based Welotec-Tag-Name. If disabled Radius Users will have no Access Tags.
     */
    #[Groups(['configuration:radius', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $radiusWelotecTagMappingEnabled = false;

    /**
     * Radius Welotec group mapping.
     */
    #[Groups(['configuration:radius'])]
    #[Assert\Valid(groups: ['configuration:radius'])]
    #[ORM\OneToMany(mappedBy: 'configuration', targetEntity: ConfigurationRadiusWelotecGroupMapping::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $radiusWelotecGroupMappings;

    /**
     * Md5 hash of loaded open source license file.
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $openSourceLicenseMd5Hash = null;

    /**
     * Single sign-on (SSO).
     */
    #[Groups(['configuration:sso', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:sso'])]
    #[ORM\Column(type: Types::STRING, enumType: SingleSignOn::class)]
    private ?SingleSignOn $singleSignOn = SingleSignOn::DISABLED;

    /**
     * Is Microsoft custom redirect URL endpoint enabled?
     */
    #[Groups(['configuration:sso', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $ssoAllowCustomRedirectUrl = false;

    /**
     * Should users with VPN permissions have VPN certificate auto-generated on first login?
     */
    #[Groups(['configuration:sso', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $ssoRoleVpnCertificateAutoGenerate = false;

    /**
     * Microsoft application (client) ID.
     */
    #[Groups(['configuration:sso', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $microsoftOidcAppId = null;

    /**
     * Microsoft credential.
     */
    #[Groups(['configuration:sso', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, enumType: MicrosoftOidcCredential::class, nullable: true)]
    private ?MicrosoftOidcCredential $microsoftOidcCredential = null;

    /**
     * Microsoft timeout in seconds.
     */
    #[Groups(['configuration:sso', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 1, groups: ['configuration:sso'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $microsoftOidcTimeout = 10;

    /**
     * Microsoft client secret.
     */
    #[Groups([AuditableInterface::ENCRYPTED_GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $microsoftOidcClientSecret = null;

    #[Groups(['configuration:sso'])]
    #[SerializedName('microsoftOidcClientSecret')]
    private ?string $decryptedMicrosoftOidcClientSecret = null;

    /**
     * Microsoft public key for uploaded certificate. TUS uploaded file.
     */
    #[Groups([AuditableInterface::ENCRYPTED_GROUP])]
    #[TusX509(groups: ['configuration:sso'])]
    #[TusX509CheckPrivateKey(propertyPath: 'microsoftOidcUploadedCertificatePrivate', groups: ['configuration:sso'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $microsoftOidcUploadedCertificatePublic = null;

    /**
     * Microsoft private key for uploaded certificate. TUS uploaded file.
     */
    #[Groups([AuditableInterface::ENCRYPTED_GROUP])]
    #[TusPrivateKey(groups: ['configuration:sso'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $microsoftOidcUploadedCertificatePrivate = null;

    /**
     * Microsoft public key valid to for uploaded certificate.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $microsoftOidcUploadedCertificatePublicValidTo = null;

    /**
     * Microsoft public key thumbprint to for uploaded certificate.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $microsoftOidcUploadedCertificatePublicThumbprint = null;

    /**
     * Microsoft public key for generated certificate.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $microsoftOidcGeneratedCertificatePublic = null;

    /**
     * Microsoft private key for generated certificate.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $microsoftOidcGeneratedCertificatePrivate = null;

    /**
     * Microsoft public key valid to for generated certificate.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $microsoftOidcGeneratedCertificatePublicValidTo = null;

    /**
     * Microsoft public key thumbprint to for generated certificate.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $microsoftOidcGeneratedCertificatePublicThumbprint = null;

    /**
     * Microsoft directory (tenant) ID.
     */
    #[Groups(['configuration:sso', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $microsoftOidcDirectoryId = null;

    /**
     * Microsoft group mapping.
     */
    #[Groups(['configuration:sso', AuditableInterface::GROUP])]
    #[Assert\Valid(groups: ['configuration:sso'])]
    #[ORM\OneToMany(mappedBy: 'configuration', targetEntity: ConfigurationMicrosoftOidcRoleMapping::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $microsoftOidcRoleMappings;

    /**
     * Should certificate be generated for microsoft credentials?
     */
    private ?bool $microsoftOidcGenerateCertificate = false;

    /**
     * Certificate expiry in days for generated for microsoft credentials.
     */
    #[Assert\GreaterThanOrEqual(value: 1, groups: ['configuration:sso'])]
    private ?int $microsoftOidcGenerateCertificateExpiryDays = null;

    public function getUploadFields(): array
    {
        return [
            // microsoftOidcUploadedCertificatePublic is listed as upload field because contents of uploaded file is placed into this field in Controller
            // microsoftOidcUploadedCertificatePrivate is listed as upload field because contents of uploaded file is placed into this field in Controller
        ];
    }

    public function addMicrosoftOidcRoleMapping(ConfigurationMicrosoftOidcRoleMapping $microsoftOidcRoleMapping)
    {
        if (!$this->microsoftOidcRoleMappings->contains($microsoftOidcRoleMapping)) {
            $this->microsoftOidcRoleMappings[] = $microsoftOidcRoleMapping;
            $microsoftOidcRoleMapping->setConfiguration($this);
        }
    }

    public function removeMicrosoftOidcRoleMapping(ConfigurationMicrosoftOidcRoleMapping $microsoftOidcRoleMapping)
    {
        if ($this->microsoftOidcRoleMappings->removeElement($microsoftOidcRoleMapping)) {
            if ($microsoftOidcRoleMapping->getConfiguration() === $this) {
                $microsoftOidcRoleMapping->setConfiguration(null);
            }
        }
    }

    public function addRadiusWelotecGroupMapping(ConfigurationRadiusWelotecGroupMapping $radiusWelotecGroupMapping)
    {
        if (!$this->radiusWelotecGroupMappings->contains($radiusWelotecGroupMapping)) {
            $this->radiusWelotecGroupMappings[] = $radiusWelotecGroupMapping;
            $radiusWelotecGroupMapping->setConfiguration($this);
        }
    }

    public function removeRadiusWelotecGroupMapping(ConfigurationRadiusWelotecGroupMapping $radiusWelotecGroupMapping)
    {
        if ($this->radiusWelotecGroupMappings->removeElement($radiusWelotecGroupMapping)) {
            if ($radiusWelotecGroupMapping->getConfiguration() === $this) {
                $radiusWelotecGroupMapping->setConfiguration(null);
            }
        }
    }

    public function __construct()
    {
        $this->radiusWelotecGroupMappings = new ArrayCollection();
        $this->microsoftOidcRoleMappings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getTotpEnabled(): ?bool
    {
        return $this->totpEnabled;
    }

    public function setTotpEnabled(?bool $totpEnabled)
    {
        $this->totpEnabled = $totpEnabled;
    }

    public function getTotpTokenLength(): ?int
    {
        return $this->totpTokenLength;
    }

    public function setTotpTokenLength(?int $totpTokenLength)
    {
        $this->totpTokenLength = $totpTokenLength;
    }

    public function getTotpSecretLength(): ?int
    {
        return $this->totpSecretLength;
    }

    public function setTotpSecretLength(?int $totpSecretLength)
    {
        $this->totpSecretLength = $totpSecretLength;
    }

    public function getTotpKeyRegeneration(): ?int
    {
        return $this->totpKeyRegeneration;
    }

    public function setTotpKeyRegeneration(?int $totpKeyRegeneration)
    {
        $this->totpKeyRegeneration = $totpKeyRegeneration;
    }

    public function getFailedLoginAttemptsLimit(): ?int
    {
        return $this->failedLoginAttemptsLimit;
    }

    public function setFailedLoginAttemptsLimit(?int $failedLoginAttemptsLimit)
    {
        $this->failedLoginAttemptsLimit = $failedLoginAttemptsLimit;
    }

    public function getFailedLoginAttemptsDisablingDuration(): ?string
    {
        return $this->failedLoginAttemptsDisablingDuration;
    }

    public function setFailedLoginAttemptsDisablingDuration(?string $failedLoginAttemptsDisablingDuration)
    {
        $this->failedLoginAttemptsDisablingDuration = $failedLoginAttemptsDisablingDuration;
    }

    public function getTotpAlgorithm(): ?TotpAlgorithm
    {
        return $this->totpAlgorithm;
    }

    public function setTotpAlgorithm(?TotpAlgorithm $totpAlgorithm)
    {
        $this->totpAlgorithm = $totpAlgorithm;
    }

    public function getTotpWindow(): ?TotpWindow
    {
        return $this->totpWindow;
    }

    public function setTotpWindow(?TotpWindow $totpWindow)
    {
        $this->totpWindow = $totpWindow;
    }

    public function getPasswordExpireDays(): ?int
    {
        return $this->passwordExpireDays;
    }

    public function setPasswordExpireDays(?int $passwordExpireDays)
    {
        $this->passwordExpireDays = $passwordExpireDays;
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

    public function getVpnConnectionLimit(): ?bool
    {
        return $this->vpnConnectionLimit;
    }

    public function setVpnConnectionLimit(?bool $vpnConnectionLimit)
    {
        $this->vpnConnectionLimit = $vpnConnectionLimit;
    }

    public function getVpnConnectionDuration(): ?string
    {
        return $this->vpnConnectionDuration;
    }

    public function setVpnConnectionDuration(?string $vpnConnectionDuration)
    {
        $this->vpnConnectionDuration = $vpnConnectionDuration;
    }

    public function getOpnsenseUrl(): ?string
    {
        return $this->opnsenseUrl;
    }

    public function setOpnsenseUrl(?string $opnsenseUrl)
    {
        $this->opnsenseUrl = $opnsenseUrl;
    }

    public function getOpnsenseApiKey(): ?string
    {
        return $this->opnsenseApiKey;
    }

    public function setOpnsenseApiKey(?string $opnsenseApiKey)
    {
        $this->opnsenseApiKey = $opnsenseApiKey;
    }

    public function getOpnsenseApiSecret(): ?string
    {
        return $this->opnsenseApiSecret;
    }

    public function setOpnsenseApiSecret(?string $opnsenseApiSecret)
    {
        $this->opnsenseApiSecret = $opnsenseApiSecret;
    }

    public function getTechniciansOpenvpnServerDescription(): ?string
    {
        return $this->techniciansOpenvpnServerDescription;
    }

    public function setTechniciansOpenvpnServerDescription(?string $techniciansOpenvpnServerDescription)
    {
        $this->techniciansOpenvpnServerDescription = $techniciansOpenvpnServerDescription;
    }

    public function getDevicesOpenvpnServerDescription(): ?string
    {
        return $this->devicesOpenvpnServerDescription;
    }

    public function setDevicesOpenvpnServerDescription(?string $devicesOpenvpnServerDescription)
    {
        $this->devicesOpenvpnServerDescription = $devicesOpenvpnServerDescription;
    }

    public function getDevicesVpnNetworks(): ?string
    {
        return $this->devicesVpnNetworks;
    }

    public function setDevicesVpnNetworks(?string $devicesVpnNetworks)
    {
        $this->devicesVpnNetworks = $devicesVpnNetworks;
    }

    public function getDevicesVirtualVpnNetworks(): ?string
    {
        return $this->devicesVirtualVpnNetworks;
    }

    public function setDevicesVirtualVpnNetworks(?string $devicesVirtualVpnNetworks)
    {
        $this->devicesVirtualVpnNetworks = $devicesVirtualVpnNetworks;
    }

    public function getTechniciansVpnNetworks(): ?string
    {
        return $this->techniciansVpnNetworks;
    }

    public function setTechniciansVpnNetworks(?string $techniciansVpnNetworks)
    {
        $this->techniciansVpnNetworks = $techniciansVpnNetworks;
    }

    public function getTechniciansOpenvpnServerIndex(): ?string
    {
        return $this->techniciansOpenvpnServerIndex;
    }

    public function setTechniciansOpenvpnServerIndex(?string $techniciansOpenvpnServerIndex)
    {
        $this->techniciansOpenvpnServerIndex = $techniciansOpenvpnServerIndex;
    }

    public function getDevicesOpenvpnServerIndex(): ?string
    {
        return $this->devicesOpenvpnServerIndex;
    }

    public function setDevicesOpenvpnServerIndex(?string $devicesOpenvpnServerIndex)
    {
        $this->devicesOpenvpnServerIndex = $devicesOpenvpnServerIndex;
    }

    public function getDevicesOvpnTemplate(): ?string
    {
        return $this->devicesOvpnTemplate;
    }

    public function setDevicesOvpnTemplate(?string $devicesOvpnTemplate)
    {
        $this->devicesOvpnTemplate = $devicesOvpnTemplate;
    }

    public function getTechniciansOvpnTemplate(): ?string
    {
        return $this->techniciansOvpnTemplate;
    }

    public function setTechniciansOvpnTemplate(?string $techniciansOvpnTemplate)
    {
        $this->techniciansOvpnTemplate = $techniciansOvpnTemplate;
    }

    public function getMaintenanceMode(): ?bool
    {
        return $this->maintenanceMode;
    }

    public function setMaintenanceMode(?bool $maintenanceMode)
    {
        $this->maintenanceMode = $maintenanceMode;
    }

    public function getAutoRemoveBackupsAfter(): ?int
    {
        return $this->autoRemoveBackupsAfter;
    }

    public function setAutoRemoveBackupsAfter(?int $autoRemoveBackupsAfter)
    {
        $this->autoRemoveBackupsAfter = $autoRemoveBackupsAfter;
    }

    public function getConfigGeneratorPhp(): ?bool
    {
        return $this->configGeneratorPhp;
    }

    public function setConfigGeneratorPhp(?bool $configGeneratorPhp)
    {
        $this->configGeneratorPhp = $configGeneratorPhp;
    }

    public function getConfigGeneratorTwig(): ?bool
    {
        return $this->configGeneratorTwig;
    }

    public function setConfigGeneratorTwig(?bool $configGeneratorTwig)
    {
        $this->configGeneratorTwig = $configGeneratorTwig;
    }

    public function getRouterIdentifier(): ?RouterIdentifier
    {
        return $this->routerIdentifier;
    }

    public function setRouterIdentifier(?RouterIdentifier $routerIdentifier)
    {
        $this->routerIdentifier = $routerIdentifier;
    }

    public function getFailedLoginAttemptsEnabled(): ?bool
    {
        return $this->failedLoginAttemptsEnabled;
    }

    public function setFailedLoginAttemptsEnabled(?bool $failedLoginAttemptsEnabled)
    {
        $this->failedLoginAttemptsEnabled = $failedLoginAttemptsEnabled;
    }

    public function getInstallationId(): ?string
    {
        return $this->installationId;
    }

    public function setInstallationId(?string $installationId)
    {
        $this->installationId = $installationId;
    }

    public function getDiskUsageAlarm(): ?int
    {
        return $this->diskUsageAlarm;
    }

    public function setDiskUsageAlarm(?int $diskUsageAlarm)
    {
        $this->diskUsageAlarm = $diskUsageAlarm;
    }

    public function getRadiusEnabled(): ?bool
    {
        return $this->radiusEnabled;
    }

    public function setRadiusEnabled(?bool $radiusEnabled)
    {
        $this->radiusEnabled = $radiusEnabled;
    }

    public function getRadiusAuth(): ?RadiusAuthenticationProtocol
    {
        return $this->radiusAuth;
    }

    public function setRadiusAuth(?RadiusAuthenticationProtocol $radiusAuth)
    {
        $this->radiusAuth = $radiusAuth;
    }

    public function getRadiusServer(): ?string
    {
        return $this->radiusServer;
    }

    public function setRadiusServer(?string $radiusServer)
    {
        $this->radiusServer = $radiusServer;
    }

    public function getRadiusSecret(): ?string
    {
        return $this->radiusSecret;
    }

    public function setRadiusSecret(?string $radiusSecret)
    {
        $this->radiusSecret = $radiusSecret;
    }

    public function getRadiusNasAddress(): ?string
    {
        return $this->radiusNasAddress;
    }

    public function setRadiusNasAddress(?string $radiusNasAddress)
    {
        $this->radiusNasAddress = $radiusNasAddress;
    }

    public function getRadiusNasPort(): ?int
    {
        return $this->radiusNasPort;
    }

    public function setRadiusNasPort(?int $radiusNasPort)
    {
        $this->radiusNasPort = $radiusNasPort;
    }

    public function getRadiusWelotecGroupMappingEnabled(): ?bool
    {
        return $this->radiusWelotecGroupMappingEnabled;
    }

    public function setRadiusWelotecGroupMappingEnabled(?bool $radiusWelotecGroupMappingEnabled)
    {
        $this->radiusWelotecGroupMappingEnabled = $radiusWelotecGroupMappingEnabled;
    }

    public function getRadiusWelotecTagMappingEnabled(): ?bool
    {
        return $this->radiusWelotecTagMappingEnabled;
    }

    public function setRadiusWelotecTagMappingEnabled(?bool $radiusWelotecTagMappingEnabled)
    {
        $this->radiusWelotecTagMappingEnabled = $radiusWelotecTagMappingEnabled;
    }

    public function getRadiusWelotecGroupMappings(): Collection
    {
        return $this->radiusWelotecGroupMappings;
    }

    public function setRadiusWelotecGroupMappings(Collection $radiusWelotecGroupMappings)
    {
        $this->radiusWelotecGroupMappings = $radiusWelotecGroupMappings;
    }

    public function getCommunicationLogsCleanupDuration(): ?int
    {
        return $this->communicationLogsCleanupDuration;
    }

    public function setCommunicationLogsCleanupDuration(?int $communicationLogsCleanupDuration)
    {
        $this->communicationLogsCleanupDuration = $communicationLogsCleanupDuration;
    }

    public function getCommunicationLogsCleanupSize(): ?int
    {
        return $this->communicationLogsCleanupSize;
    }

    public function setCommunicationLogsCleanupSize(?int $communicationLogsCleanupSize)
    {
        $this->communicationLogsCleanupSize = $communicationLogsCleanupSize;
    }

    public function getDiagnoseLogsCleanupDuration(): ?int
    {
        return $this->diagnoseLogsCleanupDuration;
    }

    public function setDiagnoseLogsCleanupDuration(?int $diagnoseLogsCleanupDuration)
    {
        $this->diagnoseLogsCleanupDuration = $diagnoseLogsCleanupDuration;
    }

    public function getDiagnoseLogsCleanupSize(): ?int
    {
        return $this->diagnoseLogsCleanupSize;
    }

    public function setDiagnoseLogsCleanupSize(?int $diagnoseLogsCleanupSize)
    {
        $this->diagnoseLogsCleanupSize = $diagnoseLogsCleanupSize;
    }

    public function getConfigLogsCleanupDuration(): ?int
    {
        return $this->configLogsCleanupDuration;
    }

    public function setConfigLogsCleanupDuration(?int $configLogsCleanupDuration)
    {
        $this->configLogsCleanupDuration = $configLogsCleanupDuration;
    }

    public function getConfigLogsCleanupSize(): ?int
    {
        return $this->configLogsCleanupSize;
    }

    public function setConfigLogsCleanupSize(?int $configLogsCleanupSize)
    {
        $this->configLogsCleanupSize = $configLogsCleanupSize;
    }

    public function getVpnLogsCleanupDuration(): ?int
    {
        return $this->vpnLogsCleanupDuration;
    }

    public function setVpnLogsCleanupDuration(?int $vpnLogsCleanupDuration)
    {
        $this->vpnLogsCleanupDuration = $vpnLogsCleanupDuration;
    }

    public function getVpnLogsCleanupSize(): ?int
    {
        return $this->vpnLogsCleanupSize;
    }

    public function setVpnLogsCleanupSize(?int $vpnLogsCleanupSize)
    {
        $this->vpnLogsCleanupSize = $vpnLogsCleanupSize;
    }

    public function getDeviceFailedLoginAttemptsCleanupDuration(): ?int
    {
        return $this->deviceFailedLoginAttemptsCleanupDuration;
    }

    public function setDeviceFailedLoginAttemptsCleanupDuration(?int $deviceFailedLoginAttemptsCleanupDuration)
    {
        $this->deviceFailedLoginAttemptsCleanupDuration = $deviceFailedLoginAttemptsCleanupDuration;
    }

    public function getDeviceFailedLoginAttemptsCleanupSize(): ?int
    {
        return $this->deviceFailedLoginAttemptsCleanupSize;
    }

    public function setDeviceFailedLoginAttemptsCleanupSize(?int $deviceFailedLoginAttemptsCleanupSize)
    {
        $this->deviceFailedLoginAttemptsCleanupSize = $deviceFailedLoginAttemptsCleanupSize;
    }

    public function getUserLoginAttemptsCleanupDuration(): ?int
    {
        return $this->userLoginAttemptsCleanupDuration;
    }

    public function setUserLoginAttemptsCleanupDuration(?int $userLoginAttemptsCleanupDuration)
    {
        $this->userLoginAttemptsCleanupDuration = $userLoginAttemptsCleanupDuration;
    }

    public function getUserLoginAttemptsCleanupSize(): ?int
    {
        return $this->userLoginAttemptsCleanupSize;
    }

    public function setUserLoginAttemptsCleanupSize(?int $userLoginAttemptsCleanupSize)
    {
        $this->userLoginAttemptsCleanupSize = $userLoginAttemptsCleanupSize;
    }

    public function getDeviceCommandsCleanupDuration(): ?int
    {
        return $this->deviceCommandsCleanupDuration;
    }

    public function setDeviceCommandsCleanupDuration(?int $deviceCommandsCleanupDuration)
    {
        $this->deviceCommandsCleanupDuration = $deviceCommandsCleanupDuration;
    }

    public function getDeviceCommandsCleanupSize(): ?int
    {
        return $this->deviceCommandsCleanupSize;
    }

    public function setDeviceCommandsCleanupSize(?int $deviceCommandsCleanupSize)
    {
        $this->deviceCommandsCleanupSize = $deviceCommandsCleanupSize;
    }

    public function getMaintenanceLogsCleanupDuration(): ?int
    {
        return $this->maintenanceLogsCleanupDuration;
    }

    public function setMaintenanceLogsCleanupDuration(?int $maintenanceLogsCleanupDuration)
    {
        $this->maintenanceLogsCleanupDuration = $maintenanceLogsCleanupDuration;
    }

    public function getMaintenanceLogsCleanupSize(): ?int
    {
        return $this->maintenanceLogsCleanupSize;
    }

    public function setMaintenanceLogsCleanupSize(?int $maintenanceLogsCleanupSize)
    {
        $this->maintenanceLogsCleanupSize = $maintenanceLogsCleanupSize;
    }

    public function getImportFileRowLogsCleanupDuration(): ?int
    {
        return $this->importFileRowLogsCleanupDuration;
    }

    public function setImportFileRowLogsCleanupDuration(?int $importFileRowLogsCleanupDuration)
    {
        $this->importFileRowLogsCleanupDuration = $importFileRowLogsCleanupDuration;
    }

    public function getImportFileRowLogsCleanupSize(): ?int
    {
        return $this->importFileRowLogsCleanupSize;
    }

    public function setImportFileRowLogsCleanupSize(?int $importFileRowLogsCleanupSize)
    {
        $this->importFileRowLogsCleanupSize = $importFileRowLogsCleanupSize;
    }

    public function getVerifyOpnsenseSslCertificate(): ?bool
    {
        return $this->verifyOpnsenseSslCertificate;
    }

    public function setVerifyOpnsenseSslCertificate(?bool $verifyOpnsenseSslCertificate)
    {
        $this->verifyOpnsenseSslCertificate = $verifyOpnsenseSslCertificate;
    }

    public function getDisableAdminRestApiDocumentation(): ?bool
    {
        return $this->disableAdminRestApiDocumentation;
    }

    public function setDisableAdminRestApiDocumentation(?bool $disableAdminRestApiDocumentation)
    {
        $this->disableAdminRestApiDocumentation = $disableAdminRestApiDocumentation;
    }

    public function getDisableSmartemsRestApiDocumentation(): ?bool
    {
        return $this->disableSmartemsRestApiDocumentation;
    }

    public function setDisableSmartemsRestApiDocumentation(?bool $disableSmartemsRestApiDocumentation)
    {
        $this->disableSmartemsRestApiDocumentation = $disableSmartemsRestApiDocumentation;
    }

    public function getDisableVpnSecuritySuiteRestApiDocumentation(): ?bool
    {
        return $this->disableVpnSecuritySuiteRestApiDocumentation;
    }

    public function setDisableVpnSecuritySuiteRestApiDocumentation(?bool $disableVpnSecuritySuiteRestApiDocumentation)
    {
        $this->disableVpnSecuritySuiteRestApiDocumentation = $disableVpnSecuritySuiteRestApiDocumentation;
    }

    public function getOpenSourceLicenseMd5Hash(): ?string
    {
        return $this->openSourceLicenseMd5Hash;
    }

    public function setOpenSourceLicenseMd5Hash(?string $openSourceLicenseMd5Hash)
    {
        $this->openSourceLicenseMd5Hash = $openSourceLicenseMd5Hash;
    }

    public function getDevicesVpnNetworksRanges(): ?string
    {
        return $this->devicesVpnNetworksRanges;
    }

    public function setDevicesVpnNetworksRanges(?string $devicesVpnNetworksRanges)
    {
        $this->devicesVpnNetworksRanges = $devicesVpnNetworksRanges;
    }

    public function getDevicesVirtualVpnNetworksRanges(): ?string
    {
        return $this->devicesVirtualVpnNetworksRanges;
    }

    public function setDevicesVirtualVpnNetworksRanges(?string $devicesVirtualVpnNetworksRanges)
    {
        $this->devicesVirtualVpnNetworksRanges = $devicesVirtualVpnNetworksRanges;
    }

    public function getTechniciansVpnNetworksRanges(): ?string
    {
        return $this->techniciansVpnNetworksRanges;
    }

    public function setTechniciansVpnNetworksRanges(?string $techniciansVpnNetworksRanges)
    {
        $this->techniciansVpnNetworksRanges = $techniciansVpnNetworksRanges;
    }

    public function getSingleSignOn(): ?SingleSignOn
    {
        return $this->singleSignOn;
    }

    public function setSingleSignOn(?SingleSignOn $singleSignOn)
    {
        $this->singleSignOn = $singleSignOn;
    }

    public function getMicrosoftOidcAppId(): ?string
    {
        return $this->microsoftOidcAppId;
    }

    public function setMicrosoftOidcAppId(?string $microsoftOidcAppId)
    {
        $this->microsoftOidcAppId = $microsoftOidcAppId;
    }

    public function getMicrosoftOidcClientSecret(): ?string
    {
        return $this->microsoftOidcClientSecret;
    }

    public function setMicrosoftOidcClientSecret(?string $microsoftOidcClientSecret)
    {
        $this->microsoftOidcClientSecret = $microsoftOidcClientSecret;
    }

    public function getMicrosoftOidcDirectoryId(): ?string
    {
        return $this->microsoftOidcDirectoryId;
    }

    public function setMicrosoftOidcDirectoryId(?string $microsoftOidcDirectoryId)
    {
        $this->microsoftOidcDirectoryId = $microsoftOidcDirectoryId;
    }

    public function getMicrosoftOidcRoleMappings(): Collection
    {
        return $this->microsoftOidcRoleMappings;
    }

    public function setMicrosoftOidcRoleMappings(Collection $microsoftOidcRoleMappings)
    {
        $this->microsoftOidcRoleMappings = $microsoftOidcRoleMappings;
    }

    public function getMicrosoftOidcCredential(): ?MicrosoftOidcCredential
    {
        return $this->microsoftOidcCredential;
    }

    public function setMicrosoftOidcCredential(?MicrosoftOidcCredential $microsoftOidcCredential)
    {
        $this->microsoftOidcCredential = $microsoftOidcCredential;
    }

    public function getMicrosoftOidcUploadedCertificatePublic(): ?string
    {
        return $this->microsoftOidcUploadedCertificatePublic;
    }

    public function setMicrosoftOidcUploadedCertificatePublic(?string $microsoftOidcUploadedCertificatePublic)
    {
        $this->microsoftOidcUploadedCertificatePublic = $microsoftOidcUploadedCertificatePublic;
    }

    public function getMicrosoftOidcUploadedCertificatePrivate(): ?string
    {
        return $this->microsoftOidcUploadedCertificatePrivate;
    }

    public function setMicrosoftOidcUploadedCertificatePrivate(?string $microsoftOidcUploadedCertificatePrivate)
    {
        $this->microsoftOidcUploadedCertificatePrivate = $microsoftOidcUploadedCertificatePrivate;
    }

    public function getMicrosoftOidcUploadedCertificatePublicValidTo(): ?\DateTime
    {
        return $this->microsoftOidcUploadedCertificatePublicValidTo;
    }

    public function setMicrosoftOidcUploadedCertificatePublicValidTo(?\DateTime $microsoftOidcUploadedCertificatePublicValidTo)
    {
        $this->microsoftOidcUploadedCertificatePublicValidTo = $microsoftOidcUploadedCertificatePublicValidTo;
    }

    public function getMicrosoftOidcGeneratedCertificatePublic(): ?string
    {
        return $this->microsoftOidcGeneratedCertificatePublic;
    }

    public function setMicrosoftOidcGeneratedCertificatePublic(?string $microsoftOidcGeneratedCertificatePublic)
    {
        $this->microsoftOidcGeneratedCertificatePublic = $microsoftOidcGeneratedCertificatePublic;
    }

    public function getMicrosoftOidcGeneratedCertificatePrivate(): ?string
    {
        return $this->microsoftOidcGeneratedCertificatePrivate;
    }

    public function setMicrosoftOidcGeneratedCertificatePrivate(?string $microsoftOidcGeneratedCertificatePrivate)
    {
        $this->microsoftOidcGeneratedCertificatePrivate = $microsoftOidcGeneratedCertificatePrivate;
    }

    public function getMicrosoftOidcGeneratedCertificatePublicValidTo(): ?\DateTime
    {
        return $this->microsoftOidcGeneratedCertificatePublicValidTo;
    }

    public function setMicrosoftOidcGeneratedCertificatePublicValidTo(?\DateTime $microsoftOidcGeneratedCertificatePublicValidTo)
    {
        $this->microsoftOidcGeneratedCertificatePublicValidTo = $microsoftOidcGeneratedCertificatePublicValidTo;
    }

    public function getMicrosoftOidcUploadedCertificatePublicThumbprint(): ?string
    {
        return $this->microsoftOidcUploadedCertificatePublicThumbprint;
    }

    public function setMicrosoftOidcUploadedCertificatePublicThumbprint(?string $microsoftOidcUploadedCertificatePublicThumbprint)
    {
        $this->microsoftOidcUploadedCertificatePublicThumbprint = $microsoftOidcUploadedCertificatePublicThumbprint;
    }

    public function getMicrosoftOidcGeneratedCertificatePublicThumbprint(): ?string
    {
        return $this->microsoftOidcGeneratedCertificatePublicThumbprint;
    }

    public function setMicrosoftOidcGeneratedCertificatePublicThumbprint(?string $microsoftOidcGeneratedCertificatePublicThumbprint)
    {
        $this->microsoftOidcGeneratedCertificatePublicThumbprint = $microsoftOidcGeneratedCertificatePublicThumbprint;
    }

    public function getMicrosoftOidcGenerateCertificate(): ?bool
    {
        return $this->microsoftOidcGenerateCertificate;
    }

    public function setMicrosoftOidcGenerateCertificate(?bool $microsoftOidcGenerateCertificate)
    {
        $this->microsoftOidcGenerateCertificate = $microsoftOidcGenerateCertificate;
    }

    public function getMicrosoftOidcGenerateCertificateExpiryDays(): ?int
    {
        return $this->microsoftOidcGenerateCertificateExpiryDays;
    }

    public function setMicrosoftOidcGenerateCertificateExpiryDays(?int $microsoftOidcGenerateCertificateExpiryDays)
    {
        $this->microsoftOidcGenerateCertificateExpiryDays = $microsoftOidcGenerateCertificateExpiryDays;
    }

    public function getAuditLogsCleanupDuration(): ?int
    {
        return $this->auditLogsCleanupDuration;
    }

    public function setAuditLogsCleanupDuration(?int $auditLogsCleanupDuration)
    {
        $this->auditLogsCleanupDuration = $auditLogsCleanupDuration;
    }

    public function getAuditLogsCleanupSize(): ?int
    {
        return $this->auditLogsCleanupSize;
    }

    public function setAuditLogsCleanupSize(?int $auditLogsCleanupSize)
    {
        $this->auditLogsCleanupSize = $auditLogsCleanupSize;
    }

    public function getDecryptedMicrosoftOidcClientSecret(): ?string
    {
        return $this->decryptedMicrosoftOidcClientSecret;
    }

    public function setDecryptedMicrosoftOidcClientSecret(?string $decryptedMicrosoftOidcClientSecret)
    {
        $this->decryptedMicrosoftOidcClientSecret = $decryptedMicrosoftOidcClientSecret;
    }

    public function getOpnsenseTimeout(): ?int
    {
        return $this->opnsenseTimeout;
    }

    public function setOpnsenseTimeout(?int $opnsenseTimeout)
    {
        $this->opnsenseTimeout = $opnsenseTimeout;
    }

    public function getMicrosoftOidcTimeout(): ?int
    {
        return $this->microsoftOidcTimeout;
    }

    public function setMicrosoftOidcTimeout(?int $microsoftOidcTimeout)
    {
        $this->microsoftOidcTimeout = $microsoftOidcTimeout;
    }

    public function getSsoAllowCustomRedirectUrl(): ?bool
    {
        return $this->ssoAllowCustomRedirectUrl;
    }

    public function setSsoAllowCustomRedirectUrl(?bool $ssoAllowCustomRedirectUrl)
    {
        $this->ssoAllowCustomRedirectUrl = $ssoAllowCustomRedirectUrl;
    }

    public function getSsoRoleVpnCertificateAutoGenerate(): ?bool
    {
        return $this->ssoRoleVpnCertificateAutoGenerate;
    }

    public function setSsoRoleVpnCertificateAutoGenerate(?bool $ssoRoleVpnCertificateAutoGenerate)
    {
        $this->ssoRoleVpnCertificateAutoGenerate = $ssoRoleVpnCertificateAutoGenerate;
    }
}
