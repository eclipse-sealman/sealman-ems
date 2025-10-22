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

use App\Enum\AuthenticationMethod;
use App\Enum\CommunicationProcedure;
use App\Enum\ConfigFormat;
use App\Enum\CredentialsSource;
use App\Enum\DeviceTypeIcon;
use App\Enum\FieldRequirement;
use App\Enum\MasqueradeType;
use App\Model\AuditableInterface;
use App\Model\FieldRequirementsInterface;
use App\Validator\Constraints\DeviceType as DeviceTypeValidator;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Carve\ApiBundle\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[UniqueEntity('name')]
#[UniqueEntity('slug')]
#[UniqueEntity('certificateCommonNamePrefix')]
#[DeviceTypeValidator(groups: ['deviceType:common'])]
class DeviceType implements DenyInterface, FieldRequirementsInterface, AuditableInterface
{
    use DenyTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Unique name of Device Type - used for identification and show to user.
     */
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    #[Groups(['deviceType:public', 'device:public', 'config:public', 'firmware:public', 'template:public', 'templateVersion:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * Icon of Device Type - used to show user for convenience.
     */
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    // 'identification' serializer group added to be used with DeviceTypeColumn
    #[Groups(['deviceType:public', 'device:public', 'identification', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, enumType: DeviceTypeIcon::class, nullable: true)]
    private ?DeviceTypeIcon $icon = DeviceTypeIcon::ROUTER;

    /**
     * Device name 'category' used in logs e.g. for TK800 - 'Router connected' for EG 'Edge gateway connected' something nice.
     */
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $deviceName = null;

    /**
     * Color of icon of Device Type - used to show user for convenience.
     */
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    #[Assert\CssColor(formats: Assert\CssColor::HEX_LONG, groups: ['deviceType:common'])]
    // 'identification' serializer group added to be used with DeviceTypeColumn
    #[Groups(['deviceType:public', 'device:public', 'identification', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $color = '#000000';

    /**
     * Unique name used for fimware (other device Type related) paths.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[Gedmo\Slug(fields: ['name'], updatable: false)]
    #[ORM\Column(type: Types::STRING)]
    private ?string $slug = null;

    /**
     * Unique prefix used for SSL certificate common name generation (certificate subject).
     */
    #[Assert\Length(max: 6, groups: ['deviceType:common'])]
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $certificateCommonNamePrefix = null;

    /**
     * Flag describes if administrator has enabled/disabled device type.
     */
    #[Groups(['deviceType:public', 'identification', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $enabled = true;

    /**
     * Is primary feature firmware configuration available - check documentation.
     */
    #[Groups(['deviceType:public', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasFirmware1 = false;

    /**
     * Primary feature firmware name usefull for user e.g. 'Firmware', 'Device supervisor PySDK package', 'Bootloader'.
     */
    #[Groups(['deviceType:public', 'deviceType:firmwareFeatureName', 'firmware:public', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'hasFirmware1', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $nameFirmware1 = 'Firmware';

    /**
     * Primary feature firmware custom URL to get firmware from.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $customUrlFirmware1 = null;

    /**
     * Is secondary feature firmware configuration available - check documentation.
     */
    #[Groups(['deviceType:public', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasFirmware2 = false;

    /**
     * Secondary feature firmware name usefull for user e.g. 'Firmware', 'Device supervisor PySDK package', 'Bootloader'.
     */
    #[Groups(['deviceType:public', 'deviceType:firmwareFeatureName', 'firmware:public', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'hasFirmware2', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $nameFirmware2 = 'Firmware2';

    /**
     * Secondary feature firmware custom URL to get firmware from.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $customUrlFirmware2 = null;

    /**
     * Is tertiary feature firmware configuration available - check documentation.
     */
    #[Groups(['deviceType:public', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasFirmware3 = false;

    /**
     * Tertiary feature firmware name usefull for user e.g. 'Firmware', 'Device supervisor PySDK package', 'Bootloader'.
     */
    #[Groups(['deviceType:public', 'deviceType:firmwareFeatureName', 'firmware:public', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'hasFirmware3', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $nameFirmware3 = 'Firmware3';

    /**
     * Tertiary feature firmware custom URL to get firmware from.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $customUrlFirmware3 = null;

    /**
     * Is primary feature config configuration available - check documentation.
     */
    #[Groups(['deviceType:public', 'template:public', 'templateVersion:public', 'device:public', 'importFileRow:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasConfig1 = false;

    /**
     * Should primary feature config reinstall flag always assumed set to true (if true reinstall flag will not be used in WebUI) - check documentation.
     */
    #[Groups(['deviceType:public', 'config:public', 'template:public', 'templateVersion:public', 'device:public', 'importFileRow:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasAlwaysReinstallConfig1 = false;

    /**
     * Primary feature config name usefull for user e.g. 'Config', 'Running config', 'Device supervisor config'.
     */
    #[Groups(['deviceType:public', 'deviceType:configFeatureName', 'config:public', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'hasConfig1', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $nameConfig1 = 'Config';

    /**
     * Primary feature config format type e.g. plain, json.
     */
    #[Groups(['deviceType:public', 'config:public', 'templateVersion:public', 'device:public', 'deviceType:configFeatureFormat', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'hasConfig1', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: ConfigFormat::class, nullable: true)]
    private ?ConfigFormat $formatConfig1 = ConfigFormat::PLAIN;

    /**
     * Is secondary feature config configuration available - check documentation.
     */
    #[Groups(['deviceType:public', 'template:public', 'templateVersion:public', 'device:public', 'importFileRow:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasConfig2 = false;

    /**
     * Should secondary feature config reinstall flag always assumed set to true (if true reinstall flag will not be used in WebUI) - check documentation.
     */
    #[Groups(['deviceType:public', 'config:public', 'template:public', 'templateVersion:public', 'device:public', 'importFileRow:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasAlwaysReinstallConfig2 = false;

    /**
     * Secondary feature config name usefull for user e.g. 'Config', 'Running config', 'Device supervisor config'.
     */
    #[Groups(['deviceType:public', 'deviceType:configFeatureName', 'config:public', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'hasConfig2', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $nameConfig2 = 'Config2';

    /**
     * Secondary feature config format type e.g. plain, json.
     */
    #[Groups(['deviceType:public', 'config:public', 'templateVersion:public', 'device:public', 'deviceType:configFeatureFormat', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'hasConfig2', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: ConfigFormat::class, nullable: true)]
    private ?ConfigFormat $formatConfig2 = ConfigFormat::PLAIN;

    /**
     * Is tertiary feature config configuration available - check documentation.
     */
    #[Groups(['deviceType:public', 'template:public', 'templateVersion:public', 'device:public', 'importFileRow:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasConfig3 = false;

    /**
     * Should tertiary feature config reinstall flag always assumed set to true (if true reinstall flag will not be used in WebUI) - check documentation.
     */
    #[Groups(['deviceType:public', 'config:public', 'template:public', 'templateVersion:public', 'device:public', 'importFileRow:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasAlwaysReinstallConfig3 = false;

    /**
     * Tertiary feature config name usefull for user e.g. 'Config', 'Running config', 'Device supervisor config'.
     */
    #[Groups(['deviceType:public', 'deviceType:configFeatureName', 'config:public', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'hasConfig3', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $nameConfig3 = 'Config3';

    /**
     * Tertiary feature config format type e.g. plain, json.
     */
    #[Groups(['deviceType:public', 'config:public', 'templateVersion:public', 'device:public', 'deviceType:configFeatureFormat', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'hasConfig3', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: ConfigFormat::class, nullable: true)]
    private ?ConfigFormat $formatConfig3 = ConfigFormat::PLAIN;

    /**
     * Authentication method during device communication - check documentation.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: AuthenticationMethod::class)]
    private ?AuthenticationMethod $authenticationMethod = AuthenticationMethod::NONE;

    /**
     * Credentials source during device communication - check documentation.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, enumType: CredentialsSource::class, nullable: true)]
    private ?CredentialsSource $credentialsSource = null;

    /**
     * Device secret type used for credentials.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: DeviceTypeSecret::class, inversedBy: 'deviceTypeSecretCredentials')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DeviceTypeSecret $deviceTypeSecretCredential = null;

    /**
     * Certificate type used for credentials.
     */
    // Cannot use DeviceTypeCertificateType due to collection issues (no DeviceTypeCertificateType ID in device type form)
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: CertificateType::class, inversedBy: 'deviceTypeCertificateTypeCredentials')]
    #[ORM\JoinColumn(nullable: true)]
    private ?CertificateType $deviceTypeCertificateTypeCredential = null;

    /**
     * Route prefix during device communication - check documentation.
     */
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $routePrefix = '/';

    /**
     * Communication procedure used during device communication - check documentation.
     */
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: CommunicationProcedure::class)]
    private ?CommunicationProcedure $communicationProcedure = CommunicationProcedure::NONE;

    /**
     * Is Certificates functionality enabled for device in this Device Type.
     */
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasCertificates = false;

    /**
     * Is VPN functionality enabled for device in this Device Type.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasVpn = false;

    /**
     * Is endpoint devices functionality enabled for device in this Device Type.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasEndpointDevices = false;

    /**
     * Is template functionality enabled for device in this Device Type.
     */
    #[Groups(['deviceType:public', 'device:public', 'importFileRow:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasTemplates = false;

    /**
     * Is device variable functionality enabled for device in this Device Type.
     */
    #[Groups(['deviceType:public', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasVariables = false;

    /**
     * Is masquereade functionality enabled for device in this Device Type.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasMasquerade = false;

    /**
     * Is GSM functionality enabled for device in this Device Type.
     */
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasGsm = false;

    /**
     * Is request config flag functionality enabled for device in this Device Type.
     */
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasRequestConfig = false;

    /**
     * Is request diagnose flag functionality enabled for device in this Device Type.
     */
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasRequestDiagnose = false;

    /**
     * How serialNumber property is validated for device in this Device Type.
     */
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: FieldRequirement::class)]
    private ?FieldRequirement $fieldSerialNumber = FieldRequirement::UNUSED;

    /**
     * How imsi property is validated for device in this Device Type.
     */
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: FieldRequirement::class)]
    private ?FieldRequirement $fieldImsi = FieldRequirement::UNUSED;

    /**
     * How model property is validated for device in this Device Type.
     */
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: FieldRequirement::class)]
    private ?FieldRequirement $fieldModel = FieldRequirement::UNUSED;

    /**
     * How registrationId property is validated for device in this Device Type.
     */
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: FieldRequirement::class)]
    private ?FieldRequirement $fieldRegistrationId = FieldRequirement::UNUSED;

    /**
     * How endorsementKey property is validated for device in this Device Type.
     */
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: FieldRequirement::class)]
    private ?FieldRequirement $fieldEndorsementKey = FieldRequirement::UNUSED;

    /**
     * How hardwareVersion property is validated for device in this Device Type.
     */
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: FieldRequirement::class)]
    private ?FieldRequirement $fieldHardwareVersion = FieldRequirement::UNUSED;

    /**
     * Is device command functionality enabled for device in this Device Type.
     */
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasDeviceCommands = false;

    /**
     * Is device to network connection functionality enabled for device in this Device Type.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasDeviceToNetworkConnection = false;

    /**
     * Max reties for Device Commands - check documentation.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['deviceType:common'])]
    #[Assert\NotBlankOnTrue(propertyPath: 'hasDeviceCommands', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $deviceCommandMaxRetries = 3;

    /**
     * Command Expire Duration for Device Commands - check documentation.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[Assert\DateModifier(groups: ['deviceType:common'])]
    #[Assert\NotBlankOnTrue(propertyPath: 'hasDeviceCommands', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $deviceCommandExpireDuration = '+4 hours';

    /**
     * Require minimal RSRP (singal strenght) to send config - check documentation.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $enableConfigMinRsrp = false;

    /**
     * Required minimal RSRP (singal strenght) value to send config - check documentation.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'enableConfigMinRsrp', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $configMinRsrp = -116;

    /**
     * Require minimal RSRP (singal strenght) to send firmware - check documentation.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $enableFirmwareMinRsrp = false;

    /**
     * Required minimal RSRP (singal strenght) value to send firmware - check documentation.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'enableFirmwareMinRsrp', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $firmwareMinRsrp = -116;

    /**
     * Are config logs automaticaly saved in database if provided by device communication - check documentation.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $enableConfigLogs = false;

    /**
     * Should this device calculate communication connections from device over set period?
     */
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $enableConnectionAggregation = false;

    /**
     * Period of time (in hours) to use for agregation calculations e.g. how many times router connected in this period.
     */
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 1, groups: ['deviceType:common'])]
    #[Assert\NotBlankOnTrue(propertyPath: 'enableConnectionAggregation', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $connectionAggregationPeriod = 24;

    /**
     * Default size of subnet as cidr in short format as in /8, /24. (/32 = 1 /24 = 256) for created device and template version.
     */
    #[Groups(['deviceType:public', 'template:public', 'templateVersion:public', 'device:public', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'hasVpn', groups: ['deviceType:common'])]
    #[Assert\Range(min: 1, max: 32, groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $virtualSubnetCidr = 32;

    /**
     * Default masquerade type (disabled, default or advanced) for created device and template version.
     */
    #[Groups(['deviceType:public', 'template:public', 'templateVersion:public', 'device:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, enumType: MasqueradeType::class, nullable: true)]
    private ?MasqueradeType $masqueradeType = MasqueradeType::DISABLED;

    #[ORM\OneToMany(mappedBy: 'deviceType', targetEntity: Device::class)]
    private Collection $devices;

    /**
     * Device type secrets.
     */
    #[ORM\OneToMany(mappedBy: 'deviceType', targetEntity: DeviceTypeSecret::class)]
    private Collection $deviceTypeSecrets;

    /**
     * Secret logs.
     */
    #[ORM\OneToMany(mappedBy: 'deviceType', targetEntity: SecretLog::class)]
    private Collection $secretLogs;

    #[ORM\OneToMany(mappedBy: 'deviceType', targetEntity: TemplateVersion::class)]
    private Collection $templateVersions;

    #[ORM\OneToMany(mappedBy: 'deviceType', targetEntity: Template::class)]
    private Collection $templates;

    #[ORM\OneToMany(mappedBy: 'deviceType', targetEntity: Config::class)]
    private Collection $configs;

    #[ORM\OneToMany(mappedBy: 'deviceType', targetEntity: Firmware::class)]
    private Collection $firmwares;

    #[ORM\OneToMany(mappedBy: 'deviceType', targetEntity: UserDeviceType::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $userDeviceTypes;

    /**
     * Certificate types settings.
     */
    #[Groups(['deviceType:public', 'device:public'])]
    #[Assert\Valid(groups: ['deviceType:common'])]
    #[ORM\OneToMany(mappedBy: 'deviceType', targetEntity: DeviceTypeCertificateType::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $certificateTypes;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    /**
     * Helper field used to provide information if VPN is available for this device type (depending on license and system state).
     */
    #[Groups(['deviceType:public', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification'])]
    private ?bool $isVpnAvailable;

    /**
     * Helper field used to provide information if Masquerade is available for this device type (depending on license and system state).
     */
    #[Groups(['deviceType:public', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification'])]
    private ?bool $isMasqueradeAvailable;

    /**
     * Helper field used to provide information if EndpointDevices are available for this device type (depending on license and system state).
     */
    #[Groups(['deviceType:public', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification'])]
    private ?bool $isEndpointDevicesAvailable;

    /**
     * Helper field used to provide information if DeviceToNetworkConnection is available for this device type (depending on license and system state).
     */
    #[Groups(['deviceType:public', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification'])]
    private ?bool $isDeviceToNetworkConnectionAvailable;

    /**
     * Helper field used to provide information if device type enabled and available (depending on license and system state).
     */
    #[Groups(['deviceType:public', 'identification', 'template:public', 'templateVersion:public', 'device:public', 'deviceType:identification'])]
    private ?bool $isAvailable = false;

    /**
     * This function returns routePrefix which is ready to concatenate with endpoint path.
     * it will not end with '/'.
     */
    public function getValidRoutePrefix(): ?string
    {
        if (!$this->getRoutePrefix()) {
            return '';
        }
        if ('/' === $this->getRoutePrefix()) {
            return '';
        }

        if (str_ends_with($this->getRoutePrefix(), '/')) {
            $routePrefix = $this->getRoutePrefix();

            while (str_ends_with($routePrefix, '/')) {
                $routePrefix = substr($routePrefix, 0, -1);
            }

            return $routePrefix;
        }

        return $this->getRoutePrefix();
    }

    public function addUser(User $user)
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addDeviceType($this);
        }
    }

    public function removeUser(User $user)
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            $user->removeDeviceType($this);
        }
    }

    public function addUserDeviceType(UserDeviceType $userDeviceType)
    {
        if (!$this->userDeviceTypes->contains($userDeviceType)) {
            $this->userDeviceTypes[] = $userDeviceType;
            $userDeviceType->setDeviceType($this);
        }
    }

    public function removeUserDeviceType(UserDeviceType $userDeviceType)
    {
        if ($this->userDeviceTypes->removeElement($userDeviceType)) {
            if ($userDeviceType->getDeviceType() === $this) {
                $userDeviceType->setDeviceType(null);
            }
        }
    }

    public function addCertificateType(DeviceTypeCertificateType $certificateType)
    {
        if (!$this->certificateTypes->contains($certificateType)) {
            $this->certificateTypes[] = $certificateType;
            $certificateType->setDeviceType($this);
        }
    }

    public function removeCertificateType(DeviceTypeCertificateType $certificateType)
    {
        if ($this->certificateTypes->removeElement($certificateType)) {
            if ($certificateType->getDeviceType() === $this) {
                $certificateType->setDeviceType(null);
            }
        }
    }

    public function __construct()
    {
        $this->configs = new ArrayCollection();
        $this->devices = new ArrayCollection();
        $this->deviceTypeSecrets = new ArrayCollection();
        $this->secretLogs = new ArrayCollection();
        $this->firmwares = new ArrayCollection();
        $this->userDeviceTypes = new ArrayCollection();
        $this->templates = new ArrayCollection();
        $this->templateVersions = new ArrayCollection();
        $this->certificateTypes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getIcon(): ?DeviceTypeIcon
    {
        return $this->icon;
    }

    public function setIcon(?DeviceTypeIcon $icon)
    {
        $this->icon = $icon;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function setDeviceName(?string $deviceName)
    {
        $this->deviceName = $deviceName;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color)
    {
        $this->color = $color;
    }

    public function getHasFirmware1(): ?bool
    {
        return $this->hasFirmware1;
    }

    public function setHasFirmware1(?bool $hasFirmware1)
    {
        $this->hasFirmware1 = $hasFirmware1;
    }

    public function getNameFirmware1(): ?string
    {
        return $this->nameFirmware1;
    }

    public function setNameFirmware1(?string $nameFirmware1)
    {
        $this->nameFirmware1 = $nameFirmware1;
    }

    public function getHasFirmware2(): ?bool
    {
        return $this->hasFirmware2;
    }

    public function setHasFirmware2(?bool $hasFirmware2)
    {
        $this->hasFirmware2 = $hasFirmware2;
    }

    public function getNameFirmware2(): ?string
    {
        return $this->nameFirmware2;
    }

    public function setNameFirmware2(?string $nameFirmware2)
    {
        $this->nameFirmware2 = $nameFirmware2;
    }

    public function getHasFirmware3(): ?bool
    {
        return $this->hasFirmware3;
    }

    public function setHasFirmware3(?bool $hasFirmware3)
    {
        $this->hasFirmware3 = $hasFirmware3;
    }

    public function getNameFirmware3(): ?string
    {
        return $this->nameFirmware3;
    }

    public function setNameFirmware3(?string $nameFirmware3)
    {
        $this->nameFirmware3 = $nameFirmware3;
    }

    public function getHasConfig1(): ?bool
    {
        return $this->hasConfig1;
    }

    public function setHasConfig1(?bool $hasConfig1)
    {
        $this->hasConfig1 = $hasConfig1;
    }

    public function getNameConfig1(): ?string
    {
        return $this->nameConfig1;
    }

    public function setNameConfig1(?string $nameConfig1)
    {
        $this->nameConfig1 = $nameConfig1;
    }

    public function getFormatConfig1(): ?ConfigFormat
    {
        return $this->formatConfig1;
    }

    public function setFormatConfig1(?ConfigFormat $formatConfig1)
    {
        $this->formatConfig1 = $formatConfig1;
    }

    public function getHasConfig2(): ?bool
    {
        return $this->hasConfig2;
    }

    public function setHasConfig2(?bool $hasConfig2)
    {
        $this->hasConfig2 = $hasConfig2;
    }

    public function getNameConfig2(): ?string
    {
        return $this->nameConfig2;
    }

    public function setNameConfig2(?string $nameConfig2)
    {
        $this->nameConfig2 = $nameConfig2;
    }

    public function getFormatConfig2(): ?ConfigFormat
    {
        return $this->formatConfig2;
    }

    public function setFormatConfig2(?ConfigFormat $formatConfig2)
    {
        $this->formatConfig2 = $formatConfig2;
    }

    public function getHasConfig3(): ?bool
    {
        return $this->hasConfig3;
    }

    public function setHasConfig3(?bool $hasConfig3)
    {
        $this->hasConfig3 = $hasConfig3;
    }

    public function getNameConfig3(): ?string
    {
        return $this->nameConfig3;
    }

    public function setNameConfig3(?string $nameConfig3)
    {
        $this->nameConfig3 = $nameConfig3;
    }

    public function getFormatConfig3(): ?ConfigFormat
    {
        return $this->formatConfig3;
    }

    public function setFormatConfig3(?ConfigFormat $formatConfig3)
    {
        $this->formatConfig3 = $formatConfig3;
    }

    public function getAuthenticationMethod(): ?AuthenticationMethod
    {
        return $this->authenticationMethod;
    }

    public function setAuthenticationMethod(?AuthenticationMethod $authenticationMethod)
    {
        $this->authenticationMethod = $authenticationMethod;
    }

    public function getRoutePrefix(): ?string
    {
        return $this->routePrefix;
    }

    public function setRoutePrefix(?string $routePrefix)
    {
        $this->routePrefix = $routePrefix;
    }

    public function getCommunicationProcedure(): ?CommunicationProcedure
    {
        return $this->communicationProcedure;
    }

    public function setCommunicationProcedure(?CommunicationProcedure $communicationProcedure)
    {
        $this->communicationProcedure = $communicationProcedure;
    }

    public function getHasCertificates(): ?bool
    {
        return $this->hasCertificates;
    }

    public function setHasCertificates(?bool $hasCertificates)
    {
        $this->hasCertificates = $hasCertificates;
    }

    public function getHasVpn(): ?bool
    {
        return $this->hasVpn;
    }

    public function setHasVpn(?bool $hasVpn)
    {
        $this->hasVpn = $hasVpn;
    }

    public function getHasEndpointDevices(): ?bool
    {
        return $this->hasEndpointDevices;
    }

    public function setHasEndpointDevices(?bool $hasEndpointDevices)
    {
        $this->hasEndpointDevices = $hasEndpointDevices;
    }

    public function getHasTemplates(): ?bool
    {
        return $this->hasTemplates;
    }

    public function setHasTemplates(?bool $hasTemplates)
    {
        $this->hasTemplates = $hasTemplates;
    }

    public function getHasVariables(): ?bool
    {
        return $this->hasVariables;
    }

    public function setHasVariables(?bool $hasVariables)
    {
        $this->hasVariables = $hasVariables;
    }

    public function getHasMasquerade(): ?bool
    {
        return $this->hasMasquerade;
    }

    public function setHasMasquerade(?bool $hasMasquerade)
    {
        $this->hasMasquerade = $hasMasquerade;
    }

    public function getHasGsm(): ?bool
    {
        return $this->hasGsm;
    }

    public function setHasGsm(?bool $hasGsm)
    {
        $this->hasGsm = $hasGsm;
    }

    public function getHasRequestConfig(): ?bool
    {
        return $this->hasRequestConfig;
    }

    public function setHasRequestConfig(?bool $hasRequestConfig)
    {
        $this->hasRequestConfig = $hasRequestConfig;
    }

    public function getHasRequestDiagnose(): ?bool
    {
        return $this->hasRequestDiagnose;
    }

    public function setHasRequestDiagnose(?bool $hasRequestDiagnose)
    {
        $this->hasRequestDiagnose = $hasRequestDiagnose;
    }

    public function getEnableConfigMinRsrp(): ?bool
    {
        return $this->enableConfigMinRsrp;
    }

    public function setEnableConfigMinRsrp(?bool $enableConfigMinRsrp)
    {
        $this->enableConfigMinRsrp = $enableConfigMinRsrp;
    }

    public function getConfigMinRsrp(): ?int
    {
        return $this->configMinRsrp;
    }

    public function setConfigMinRsrp(?int $configMinRsrp)
    {
        $this->configMinRsrp = $configMinRsrp;
    }

    public function getEnableFirmwareMinRsrp(): ?bool
    {
        return $this->enableFirmwareMinRsrp;
    }

    public function setEnableFirmwareMinRsrp(?bool $enableFirmwareMinRsrp)
    {
        $this->enableFirmwareMinRsrp = $enableFirmwareMinRsrp;
    }

    public function getFirmwareMinRsrp(): ?int
    {
        return $this->firmwareMinRsrp;
    }

    public function setFirmwareMinRsrp(?int $firmwareMinRsrp)
    {
        $this->firmwareMinRsrp = $firmwareMinRsrp;
    }

    public function getEnableConfigLogs(): ?bool
    {
        return $this->enableConfigLogs;
    }

    public function setEnableConfigLogs(?bool $enableConfigLogs)
    {
        $this->enableConfigLogs = $enableConfigLogs;
    }

    public function getCustomUrlFirmware1(): ?string
    {
        return $this->customUrlFirmware1;
    }

    public function setCustomUrlFirmware1(?string $customUrlFirmware1)
    {
        $this->customUrlFirmware1 = $customUrlFirmware1;
    }

    public function getCustomUrlFirmware2(): ?string
    {
        return $this->customUrlFirmware2;
    }

    public function setCustomUrlFirmware2(?string $customUrlFirmware2)
    {
        $this->customUrlFirmware2 = $customUrlFirmware2;
    }

    public function getCustomUrlFirmware3(): ?string
    {
        return $this->customUrlFirmware3;
    }

    public function setCustomUrlFirmware3(?string $customUrlFirmware3)
    {
        $this->customUrlFirmware3 = $customUrlFirmware3;
    }

    public function getEnableConnectionAggregation(): ?bool
    {
        return $this->enableConnectionAggregation;
    }

    public function setEnableConnectionAggregation(?bool $enableConnectionAggregation)
    {
        $this->enableConnectionAggregation = $enableConnectionAggregation;
    }

    public function getConnectionAggregationPeriod(): ?int
    {
        return $this->connectionAggregationPeriod;
    }

    public function setConnectionAggregationPeriod(?int $connectionAggregationPeriod)
    {
        $this->connectionAggregationPeriod = $connectionAggregationPeriod;
    }

    public function getDevices(): Collection
    {
        return $this->devices;
    }

    public function setDevices(Collection $devices)
    {
        $this->devices = $devices;
    }

    public function getTemplateVersions(): Collection
    {
        return $this->templateVersions;
    }

    public function setTemplateVersions(Collection $templateVersions)
    {
        $this->templateVersions = $templateVersions;
    }

    public function getTemplates(): Collection
    {
        return $this->templates;
    }

    public function setTemplates(Collection $templates)
    {
        $this->templates = $templates;
    }

    public function getConfigs(): Collection
    {
        return $this->configs;
    }

    public function setConfigs(Collection $configs)
    {
        $this->configs = $configs;
    }

    public function getFirmwares(): Collection
    {
        return $this->firmwares;
    }

    public function setFirmwares(Collection $firmwares)
    {
        $this->firmwares = $firmwares;
    }

    public function getUserDeviceTypes(): Collection
    {
        return $this->userDeviceTypes;
    }

    public function setUserDeviceTypes(Collection $userDeviceTypes)
    {
        $this->userDeviceTypes = $userDeviceTypes;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug)
    {
        $this->slug = $slug;
    }

    public function getHasDeviceCommands(): ?bool
    {
        return $this->hasDeviceCommands;
    }

    public function setHasDeviceCommands(?bool $hasDeviceCommands)
    {
        $this->hasDeviceCommands = $hasDeviceCommands;
    }

    public function getDeviceCommandMaxRetries(): ?int
    {
        return $this->deviceCommandMaxRetries;
    }

    public function setDeviceCommandMaxRetries(?int $deviceCommandMaxRetries)
    {
        $this->deviceCommandMaxRetries = $deviceCommandMaxRetries;
    }

    public function getDeviceCommandExpireDuration(): ?string
    {
        return $this->deviceCommandExpireDuration;
    }

    public function setDeviceCommandExpireDuration(?string $deviceCommandExpireDuration)
    {
        $this->deviceCommandExpireDuration = $deviceCommandExpireDuration;
    }

    public function getHasDeviceToNetworkConnection(): ?bool
    {
        return $this->hasDeviceToNetworkConnection;
    }

    public function setHasDeviceToNetworkConnection(?bool $hasDeviceToNetworkConnection)
    {
        $this->hasDeviceToNetworkConnection = $hasDeviceToNetworkConnection;
    }

    public function getVirtualSubnetCidr(): ?int
    {
        return $this->virtualSubnetCidr;
    }

    public function setVirtualSubnetCidr(?int $virtualSubnetCidr)
    {
        $this->virtualSubnetCidr = $virtualSubnetCidr;
    }

    public function getIsVpnAvailable(): ?bool
    {
        return $this->isVpnAvailable;
    }

    public function setIsVpnAvailable(?bool $isVpnAvailable)
    {
        $this->isVpnAvailable = $isVpnAvailable;
    }

    public function getIsMasqueradeAvailable(): ?bool
    {
        return $this->isMasqueradeAvailable;
    }

    public function setIsMasqueradeAvailable(?bool $isMasqueradeAvailable)
    {
        $this->isMasqueradeAvailable = $isMasqueradeAvailable;
    }

    public function getIsEndpointDevicesAvailable(): ?bool
    {
        return $this->isEndpointDevicesAvailable;
    }

    public function setIsEndpointDevicesAvailable(?bool $isEndpointDevicesAvailable)
    {
        $this->isEndpointDevicesAvailable = $isEndpointDevicesAvailable;
    }

    public function getIsDeviceToNetworkConnectionAvailable(): ?bool
    {
        return $this->isDeviceToNetworkConnectionAvailable;
    }

    public function setIsDeviceToNetworkConnectionAvailable(?bool $isDeviceToNetworkConnectionAvailable)
    {
        $this->isDeviceToNetworkConnectionAvailable = $isDeviceToNetworkConnectionAvailable;
    }

    public function getIsAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(?bool $isAvailable)
    {
        $this->isAvailable = $isAvailable;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled)
    {
        $this->enabled = $enabled;
    }

    public function getMasqueradeType(): ?MasqueradeType
    {
        return $this->masqueradeType;
    }

    public function setMasqueradeType(?MasqueradeType $masqueradeType)
    {
        $this->masqueradeType = $masqueradeType;
    }

    public function getFieldSerialNumber(): ?FieldRequirement
    {
        return $this->fieldSerialNumber;
    }

    public function setFieldSerialNumber(?FieldRequirement $fieldSerialNumber)
    {
        $this->fieldSerialNumber = $fieldSerialNumber;
    }

    public function getFieldImsi(): ?FieldRequirement
    {
        return $this->fieldImsi;
    }

    public function setFieldImsi(?FieldRequirement $fieldImsi)
    {
        $this->fieldImsi = $fieldImsi;
    }

    public function getFieldModel(): ?FieldRequirement
    {
        return $this->fieldModel;
    }

    public function setFieldModel(?FieldRequirement $fieldModel)
    {
        $this->fieldModel = $fieldModel;
    }

    public function getFieldRegistrationId(): ?FieldRequirement
    {
        return $this->fieldRegistrationId;
    }

    public function setFieldRegistrationId(?FieldRequirement $fieldRegistrationId)
    {
        $this->fieldRegistrationId = $fieldRegistrationId;
    }

    public function getFieldEndorsementKey(): ?FieldRequirement
    {
        return $this->fieldEndorsementKey;
    }

    public function setFieldEndorsementKey(?FieldRequirement $fieldEndorsementKey)
    {
        $this->fieldEndorsementKey = $fieldEndorsementKey;
    }

    public function getFieldHardwareVersion(): ?FieldRequirement
    {
        return $this->fieldHardwareVersion;
    }

    public function setFieldHardwareVersion(?FieldRequirement $fieldHardwareVersion)
    {
        $this->fieldHardwareVersion = $fieldHardwareVersion;
    }

    public function getHasAlwaysReinstallConfig1(): ?bool
    {
        return $this->hasAlwaysReinstallConfig1;
    }

    public function setHasAlwaysReinstallConfig1(?bool $hasAlwaysReinstallConfig1)
    {
        $this->hasAlwaysReinstallConfig1 = $hasAlwaysReinstallConfig1;
    }

    public function getHasAlwaysReinstallConfig2(): ?bool
    {
        return $this->hasAlwaysReinstallConfig2;
    }

    public function setHasAlwaysReinstallConfig2(?bool $hasAlwaysReinstallConfig2)
    {
        $this->hasAlwaysReinstallConfig2 = $hasAlwaysReinstallConfig2;
    }

    public function getHasAlwaysReinstallConfig3(): ?bool
    {
        return $this->hasAlwaysReinstallConfig3;
    }

    public function setHasAlwaysReinstallConfig3(?bool $hasAlwaysReinstallConfig3)
    {
        $this->hasAlwaysReinstallConfig3 = $hasAlwaysReinstallConfig3;
    }

    public function getCertificateTypes(): Collection
    {
        return $this->certificateTypes;
    }

    public function setCertificateTypes(Collection $certificateTypes)
    {
        $this->certificateTypes = $certificateTypes;
    }

    public function getCertificateCommonNamePrefix(): ?string
    {
        return $this->certificateCommonNamePrefix;
    }

    public function setCertificateCommonNamePrefix(?string $certificateCommonNamePrefix)
    {
        $this->certificateCommonNamePrefix = $certificateCommonNamePrefix;
    }

    public function getDeviceTypeSecrets(): Collection
    {
        return $this->deviceTypeSecrets;
    }

    public function setDeviceTypeSecrets(Collection $deviceTypeSecrets)
    {
        $this->deviceTypeSecrets = $deviceTypeSecrets;
    }

    public function getSecretLogs(): Collection
    {
        return $this->secretLogs;
    }

    public function setSecretLogs(Collection $secretLogs)
    {
        $this->secretLogs = $secretLogs;
    }

    public function getDeviceTypeSecretCredential(): ?DeviceTypeSecret
    {
        return $this->deviceTypeSecretCredential;
    }

    public function setDeviceTypeSecretCredential(?DeviceTypeSecret $deviceTypeSecretCredential)
    {
        $this->deviceTypeSecretCredential = $deviceTypeSecretCredential;
    }

    public function getCredentialsSource(): ?CredentialsSource
    {
        return $this->credentialsSource;
    }

    public function setCredentialsSource(?CredentialsSource $credentialsSource)
    {
        $this->credentialsSource = $credentialsSource;
    }

    public function getDeviceTypeCertificateTypeCredential(): ?CertificateType
    {
        return $this->deviceTypeCertificateTypeCredential;
    }

    public function setDeviceTypeCertificateTypeCredential(?CertificateType $deviceTypeCertificateTypeCredential)
    {
        $this->deviceTypeCertificateTypeCredential = $deviceTypeCertificateTypeCredential;
    }
}
