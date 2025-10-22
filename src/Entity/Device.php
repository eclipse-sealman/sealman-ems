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

use App\Entity\Traits\AccessTagsInterface;
use App\Entity\Traits\BlameableEntityInterface;
use App\Entity\Traits\BlameableEntityTrait;
use App\Entity\Traits\CommunicationEntityInterface;
use App\Entity\Traits\CommunicationEntityTrait;
use App\Entity\Traits\FirmwareStatusEntityInterface;
use App\Entity\Traits\FirmwareStatusEntityTrait;
use App\Entity\Traits\GsmEntityInterface;
use App\Entity\Traits\GsmEntityTrait;
use App\Entity\Traits\InjectedAccessTagsInterface;
use App\Entity\Traits\InjectedAccessTagsTrait;
use App\Entity\Traits\InjectedEndpointDevicesInterface;
use App\Entity\Traits\InjectedEndpointDevicesTrait;
use App\Entity\Traits\TimestampableEntityInterface;
use App\Entity\Traits\TimestampableEntityTrait;
use App\Entity\Traits\VpnClientDeviceEntityTrait;
use App\Entity\Traits\VpnClientEntityInterface;
use App\Entity\Traits\VpnEntityInteface;
use App\Enum\MasqueradeType;
use App\Model\AuditableInterface;
use App\Model\DeviceLock;
use App\Model\UseableCertificate;
use App\Validator\Constraints\AvailableDeviceType;
use App\Validator\Constraints\Device as DeviceValidator;
use App\Validator\Constraints\DeviceLock as DeviceLockValidator;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[DeviceValidator(groups: ['device:common'])]
#[DeviceLockValidator(groups: ['device:lock'])]
class Device implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, GsmEntityInterface, FirmwareStatusEntityInterface, CommunicationEntityInterface, VpnClientEntityInterface, VpnEntityInteface, AccessTagsInterface, AuditableInterface, InjectedEndpointDevicesInterface, InjectedAccessTagsInterface
{
    use DenyTrait;
    use VpnClientDeviceEntityTrait;
    use GsmEntityTrait;
    use FirmwareStatusEntityTrait;
    use CommunicationEntityTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;
    use InjectedEndpointDevicesTrait;
    use InjectedAccessTagsTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Device type.
     */
    #[Groups(['device:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['device:create'])]
    #[AvailableDeviceType(groups: ['device:create'])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class, inversedBy: 'devices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DeviceType $deviceType = null;

    /**
     * Device identifier.
     */
    #[Groups(['device:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $identifier = null;

    /**
     * Device name.
     */
    #[Assert\NotBlank(groups: ['device:common'])]
    #[Groups(['device:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * Device description.
     */
    #[Groups(['device:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Masquerade type (disabled, default or advanced).
     */
    #[Groups(['device:adminVpn', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, enumType: MasqueradeType::class, nullable: true)]
    private ?MasqueradeType $masqueradeType = null;

    /**
     * UUID.
     */
    #[Groups(['device:admin', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $uuid = null;

    /**
     * Hash identifier is used for device firmware download authentication.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $hashIdentifier = null;

    /**
     * Is device enabled?
     */
    #[Groups(['device:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $enabled = false;

    /**
     * Should device use staging template first?
     */
    #[Groups(['device:admin', 'device:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $staging = false;

    /**
     * Command retry counter.
     */
    #[Groups(['device:admin', 'device:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $commandRetryCount = 0;

    /**
     * Is last command status critical?
     */
    #[Groups(['device:admin', 'device:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $lastCommandCritical = false;

    /**
     * Connection amount established from connectionAmountFrom timestamp.
     */
    #[Groups(['device:admin', 'device:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $connectionAmount = 0;

    /**
     * Timestamp from which counting of amount of established connections has been started.
     */
    #[Groups(['device:admin', 'device:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $connectionAmountFrom = null;

    /**
     * Should primary firmware be reinstalled?
     */
    #[Groups(['device:admin', 'device:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $reinstallFirmware1 = false;

    /**
     * Should secondary firmware be reinstalled?
     */
    #[Groups(['device:admin', 'device:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $reinstallFirmware2 = false;

    /**
     * Should tertiary firmware be reinstalled?
     */
    #[Groups(['device:admin', 'device:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $reinstallFirmware3 = false;

    /**
     * Should primary config be reinstalled?
     */
    #[Groups(['device:admin', 'device:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $reinstallConfig1 = false;

    /**
     * Should secondary config be reinstalled?
     */
    #[Groups(['device:admin', 'device:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $reinstallConfig2 = false;

    /**
     * Should tertiary config be reinstalled?
     */
    #[Groups(['device:admin', 'device:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $reinstallConfig3 = false;

    /**
     * Should diagnose data be requested on next communication?
     */
    #[Groups(['device:admin', 'device:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $requestDiagnoseData = false;

    /**
     * Should config data be requested on next communication?
     */
    #[Groups(['device:admin', 'device:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $requestConfigData = false;

    /**
     * Size of subnet as CIDR stored as integer (i.e. /32 = 1, /30 = 4, /24 = 256).
     */
    #[Groups(['device:adminVpn', 'device:vpnEndpointDevices', 'deviceEndpointDevice:public', AuditableInterface::GROUP])]
    #[Assert\Range(min: 1, max: 32, groups: ['device:common'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $virtualSubnetCidr = null;

    /**
     * Access tags.
     */
    #[Groups(['device:admin', 'device:smartems', AuditableInterface::GROUP])]
    #[ORM\ManyToMany(inversedBy: 'devices', targetEntity: AccessTag::class)]
    private Collection $accessTags;

    /**
     * Labels.
     */
    #[Groups(['device:public', AuditableInterface::GROUP])]
    #[ORM\ManyToMany(inversedBy: 'devices', targetEntity: Label::class)]
    private Collection $labels;

    /**
     * Template.
     */
    #[Groups(['device:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: Template::class, inversedBy: 'devices')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Template $template = null;

    /**
     * Endpoint devices.
     */
    #[Groups(['device:adminVpn', 'device:vpn', 'deviceEndpointDevice:public'])]
    #[Assert\Valid(groups: ['device:common', 'deviceEndpointDevice:lock'])]
    #[ORM\OneToMany(mappedBy: 'device', targetEntity: DeviceEndpointDevice::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $endpointDevices;

    /**
     * Masquerades.
     */
    #[Groups(['device:adminVpn'])]
    #[Assert\Valid(groups: ['device:common'])]
    #[ORM\OneToMany(mappedBy: 'device', targetEntity: DeviceMasquerade::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $masquerades;

    /**
     * Variables.
     */
    #[Groups(['device:public'])]
    #[Assert\Valid(groups: ['device:common'])]
    #[ORM\OneToMany(mappedBy: 'device', targetEntity: DeviceVariable::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $variables;

    /**
     * Communication logs.
     */
    #[ORM\OneToMany(mappedBy: 'device', targetEntity: CommunicationLog::class)]
    private Collection $communicationLogs;

    /**
     * Config logs.
     */
    #[ORM\OneToMany(mappedBy: 'device', targetEntity: ConfigLog::class)]
    private Collection $configLogs;

    /**
     * Diagnose logs.
     */
    #[ORM\OneToMany(mappedBy: 'device', targetEntity: DiagnoseLog::class)]
    private Collection $diagnoseLogs;

    /**
     * VPN logs.
     */
    #[ORM\OneToMany(mappedBy: 'device', targetEntity: VpnLog::class)]
    private Collection $vpnLogs;

    /**
     * Secret logs.
     */
    #[ORM\OneToMany(mappedBy: 'device', targetEntity: SecretLog::class)]
    private Collection $secretLogs;

    /**
     * VPN connections.
     */
    #[Groups(['device:adminVpn', 'device:vpn'])]
    #[ORM\OneToMany(mappedBy: 'device', targetEntity: VpnConnection::class)]
    private Collection $vpnConnections;

    /**
     * Commands.
     */
    #[ORM\OneToMany(mappedBy: 'device', targetEntity: DeviceCommand::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $commands;

    /**
     * Device secrets.
     */
    #[Groups(['device:public'])]
    #[ORM\OneToMany(mappedBy: 'device', targetEntity: DeviceSecret::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $deviceSecrets;

    /**
     * Device certificates.
     */
    #[Groups(['certificate:admin', 'certificate:vpn', 'certificate:smartems'])]
    #[ORM\OneToMany(mappedBy: 'device', targetEntity: Certificate::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $certificates;

    /**
     * Owned VPN connections.
     */
    #[Groups(['device:adminVpn', 'device:vpn'])]
    #[OA\Property(type: 'array', items: new OA\Items(ref: new NA\Model(type: VpnConnection::class)))]
    private Collection $ownedVpnConnections;

    /**
     * Helper field for certificates deny handling. Using UsableCertificate model.
     */
    #[Groups(['certificate:admin', 'certificate:vpn', 'certificate:smartems'])]
    #[OA\Property(type: 'array', items: new OA\Items(ref: new NA\Model(type: UseableCertificate::class)))]
    private Collection $useableCertificates;

    /**
     * Helper field for handling certificates behaviours values provided by user. Using UsableCertificate model.
     */
    #[Assert\Valid(groups: ['device:common'])]
    private ?Collection $certificateBehaviours = null;

    /**
     * Helper field used to provide information if device has device secrets available for user.
     */
    #[Groups(['device:public'])]
    private ?bool $hasDeviceSecrets = false;

    /**
     * Helper field used for validation. Read more in App\Model\DeviceLock.
     */
    private ?DeviceLock $lock = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    public function getTemplateVersion(): ?TemplateVersion
    {
        if ($this->getStaging() && $this->getTemplate()?->getStagingTemplate()) {
            return $this->getTemplate()->getStagingTemplate();
        }

        return $this->getTemplate()->getProductionTemplate();
    }

    public function addVariable(DeviceVariable $variable)
    {
        if (!$this->variables->contains($variable)) {
            $this->variables[] = $variable;
            $variable->setDevice($this);
        }
    }

    public function removeVariable(DeviceVariable $variable)
    {
        if ($this->variables->removeElement($variable)) {
            if ($variable->getDevice() === $this) {
                $variable->setDevice(null);
            }
        }
    }

    public function addAccessTag(AccessTag $accessTag)
    {
        if (!$this->accessTags->contains($accessTag)) {
            $this->accessTags->add($accessTag);
            $accessTag->addDevice($this);
        }
    }

    public function removeAccessTag(AccessTag $accessTag)
    {
        if ($this->accessTags->contains($accessTag)) {
            $this->accessTags->removeElement($accessTag);
            $accessTag->removeDevice($this);
        }
    }

    public function addLabel(Label $label)
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
            $label->addDevice($this);
        }
    }

    public function removeLabel(Label $label)
    {
        if ($this->labels->contains($label)) {
            $this->labels->removeElement($label);
            $label->removeDevice($this);
        }
    }

    public function addDeviceSecret(DeviceSecret $deviceSecret)
    {
        if (!$this->deviceSecrets->contains($deviceSecret)) {
            $this->deviceSecrets->add($deviceSecret);
            $deviceSecret->setDevice($this);
        }
    }

    public function removeDeviceSecret(DeviceSecret $deviceSecret)
    {
        if ($this->deviceSecrets->contains($deviceSecret)) {
            $this->deviceSecrets->removeElement($deviceSecret);
            $deviceSecret->setDevice(null);
        }
    }

    public function addEndpointDevice(DeviceEndpointDevice $endpointDevice)
    {
        if (!$this->endpointDevices->contains($endpointDevice)) {
            $this->endpointDevices[] = $endpointDevice;
            $endpointDevice->setDevice($this);
        }
    }

    public function removeEndpointDevice(DeviceEndpointDevice $endpointDevice)
    {
        if ($this->endpointDevices->removeElement($endpointDevice)) {
            if ($endpointDevice->getDevice() === $this) {
                $endpointDevice->setDevice(null);
            }
        }
    }

    public function addMasquerade(DeviceMasquerade $deviceMasquerade)
    {
        if (!$this->masquerades->contains($deviceMasquerade)) {
            $this->masquerades[] = $deviceMasquerade;
            $deviceMasquerade->setDevice($this);
        }
    }

    public function removeMasquerade(DeviceMasquerade $deviceMasquerade)
    {
        if ($this->masquerades->removeElement($deviceMasquerade)) {
            if ($deviceMasquerade->getDevice() === $this) {
                $deviceMasquerade->setDevice(null);
            }
        }
    }

    public function addCertificate(Certificate $certificate)
    {
        if (!$this->certificates->contains($certificate)) {
            $this->certificates[] = $certificate;
            $certificate->setDevice($this);
        }
    }

    public function removeCertificate(Certificate $certificate)
    {
        if ($this->certificates->removeElement($certificate)) {
            if ($certificate->getDevice() === $this) {
                $certificate->setDevice(null);
            }
        }
    }

    public function addVpnConnection(VpnConnection $vpnConnection)
    {
        if (!$this->vpnConnections->contains($vpnConnection)) {
            $this->vpnConnections[] = $vpnConnection;
            $vpnConnection->setDevice($this);
        }
    }

    public function removeVpnConnection(VpnConnection $vpnConnection)
    {
        if ($this->vpnConnections->removeElement($vpnConnection)) {
            if ($vpnConnection->getDevice() === $this) {
                $vpnConnection->setDevice(null);
            }
        }
    }

    public function __construct()
    {
        $this->masquerades = new ArrayCollection();
        $this->endpointDevices = new ArrayCollection();
        $this->accessTags = new ArrayCollection();
        $this->labels = new ArrayCollection();
        $this->variables = new ArrayCollection();
        $this->deviceSecrets = new ArrayCollection();
        $this->communicationLogs = new ArrayCollection();
        $this->configLogs = new ArrayCollection();
        $this->diagnoseLogs = new ArrayCollection();
        $this->vpnLogs = new ArrayCollection();
        $this->secretLogs = new ArrayCollection();
        $this->vpnConnections = new ArrayCollection();
        $this->commands = new ArrayCollection();
        $this->certificates = new ArrayCollection();
        $this->useableCertificates = new ArrayCollection();
        $this->certificateBehaviours = new ArrayCollection();
        $this->ownedVpnConnections = new ArrayCollection();
        $this->template = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getDeviceType(): ?DeviceType
    {
        return $this->deviceType;
    }

    public function setDeviceType(?DeviceType $deviceType)
    {
        $this->deviceType = $deviceType;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier)
    {
        $this->identifier = $identifier;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled)
    {
        $this->enabled = $enabled;
    }

    public function getConnectionAmount(): ?int
    {
        return $this->connectionAmount;
    }

    public function setConnectionAmount(?int $connectionAmount)
    {
        $this->connectionAmount = $connectionAmount;
    }

    public function getConnectionAmountFrom(): ?\DateTime
    {
        return $this->connectionAmountFrom;
    }

    public function setConnectionAmountFrom(?\DateTime $connectionAmountFrom)
    {
        $this->connectionAmountFrom = $connectionAmountFrom;
    }

    public function getReinstallFirmware1(): ?bool
    {
        return $this->reinstallFirmware1;
    }

    public function setReinstallFirmware1(?bool $reinstallFirmware1)
    {
        $this->reinstallFirmware1 = $reinstallFirmware1;
    }

    public function getReinstallFirmware2(): ?bool
    {
        return $this->reinstallFirmware2;
    }

    public function setReinstallFirmware2(?bool $reinstallFirmware2)
    {
        $this->reinstallFirmware2 = $reinstallFirmware2;
    }

    public function getReinstallFirmware3(): ?bool
    {
        return $this->reinstallFirmware3;
    }

    public function setReinstallFirmware3(?bool $reinstallFirmware3)
    {
        $this->reinstallFirmware3 = $reinstallFirmware3;
    }

    public function getReinstallConfig1(): ?bool
    {
        return $this->reinstallConfig1;
    }

    public function setReinstallConfig1(?bool $reinstallConfig1)
    {
        $this->reinstallConfig1 = $reinstallConfig1;
    }

    public function getReinstallConfig2(): ?bool
    {
        return $this->reinstallConfig2;
    }

    public function setReinstallConfig2(?bool $reinstallConfig2)
    {
        $this->reinstallConfig2 = $reinstallConfig2;
    }

    public function getReinstallConfig3(): ?bool
    {
        return $this->reinstallConfig3;
    }

    public function setReinstallConfig3(?bool $reinstallConfig3)
    {
        $this->reinstallConfig3 = $reinstallConfig3;
    }

    public function getRequestDiagnoseData(): ?bool
    {
        return $this->requestDiagnoseData;
    }

    public function setRequestDiagnoseData(?bool $requestDiagnoseData)
    {
        $this->requestDiagnoseData = $requestDiagnoseData;
    }

    public function getRequestConfigData(): ?bool
    {
        return $this->requestConfigData;
    }

    public function setRequestConfigData(?bool $requestConfigData)
    {
        $this->requestConfigData = $requestConfigData;
    }

    public function getAccessTags(): Collection
    {
        return $this->accessTags;
    }

    public function setAccessTags(Collection $accessTags)
    {
        $this->accessTags = $accessTags;
    }

    public function getTemplate(): ?Template
    {
        return $this->template;
    }

    public function setTemplate(?Template $template)
    {
        $this->template = $template;
    }

    public function getEndpointDevices(): Collection
    {
        return $this->endpointDevices;
    }

    public function setEndpointDevices(Collection $endpointDevices)
    {
        $this->endpointDevices = $endpointDevices;
    }

    public function getMasquerades(): Collection
    {
        return $this->masquerades;
    }

    public function setMasquerades(Collection $masquerades)
    {
        $this->masquerades = $masquerades;
    }

    public function getVariables(): Collection
    {
        return $this->variables;
    }

    public function setVariables(Collection $variables)
    {
        $this->variables = $variables;
    }

    public function getCommunicationLogs(): Collection
    {
        return $this->communicationLogs;
    }

    public function setCommunicationLogs(Collection $communicationLogs)
    {
        $this->communicationLogs = $communicationLogs;
    }

    public function getConfigLogs(): Collection
    {
        return $this->configLogs;
    }

    public function setConfigLogs(Collection $configLogs)
    {
        $this->configLogs = $configLogs;
    }

    public function getDiagnoseLogs(): Collection
    {
        return $this->diagnoseLogs;
    }

    public function setDiagnoseLogs(Collection $diagnoseLogs)
    {
        $this->diagnoseLogs = $diagnoseLogs;
    }

    public function getVpnLogs(): Collection
    {
        return $this->vpnLogs;
    }

    public function setVpnLogs(Collection $vpnLogs)
    {
        $this->vpnLogs = $vpnLogs;
    }

    public function getStaging(): ?bool
    {
        return $this->staging;
    }

    public function setStaging(?bool $staging)
    {
        $this->staging = $staging;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getMasqueradeType(): ?MasqueradeType
    {
        return $this->masqueradeType;
    }

    public function setMasqueradeType(?MasqueradeType $masqueradeType)
    {
        $this->masqueradeType = $masqueradeType;
    }

    public function getCommands(): Collection
    {
        return $this->commands;
    }

    public function setCommands(Collection $commands)
    {
        $this->commands = $commands;
    }

    public function getCommandRetryCount(): ?int
    {
        return $this->commandRetryCount;
    }

    public function setCommandRetryCount(?int $commandRetryCount)
    {
        $this->commandRetryCount = $commandRetryCount;
    }

    public function getLastCommandCritical(): ?bool
    {
        return $this->lastCommandCritical;
    }

    public function setLastCommandCritical(?bool $lastCommandCritical)
    {
        $this->lastCommandCritical = $lastCommandCritical;
    }

    public function getVirtualSubnetCidr(): ?int
    {
        return $this->virtualSubnetCidr;
    }

    public function setVirtualSubnetCidr(?int $virtualSubnetCidr)
    {
        $this->virtualSubnetCidr = $virtualSubnetCidr;
    }

    public function getVpnConnections(): Collection
    {
        return $this->vpnConnections;
    }

    public function setVpnConnections(Collection $vpnConnections)
    {
        $this->vpnConnections = $vpnConnections;
    }

    public function getLabels(): Collection
    {
        return $this->labels;
    }

    public function setLabels(Collection $labels)
    {
        $this->labels = $labels;
    }

    public function getOwnedVpnConnections(): Collection
    {
        return $this->ownedVpnConnections;
    }

    public function setOwnedVpnConnections(Collection $ownedVpnConnections)
    {
        $this->ownedVpnConnections = $ownedVpnConnections;
    }

    public function getCertificates(): Collection
    {
        return $this->certificates;
    }

    public function setCertificates(Collection $certificates)
    {
        $this->certificates = $certificates;
    }

    public function getUseableCertificates(): Collection
    {
        return $this->useableCertificates;
    }

    public function setUseableCertificates(Collection $useableCertificates)
    {
        $this->useableCertificates = $useableCertificates;
    }

    public function getCertificateBehaviours(): ?Collection
    {
        return $this->certificateBehaviours;
    }

    public function setCertificateBehaviours(?Collection $certificateBehaviours)
    {
        $this->certificateBehaviours = $certificateBehaviours;
    }

    public function getDeviceSecrets(): Collection
    {
        return $this->deviceSecrets;
    }

    public function setDeviceSecrets(Collection $deviceSecrets)
    {
        $this->deviceSecrets = $deviceSecrets;
    }

    public function getSecretLogs(): Collection
    {
        return $this->secretLogs;
    }

    public function setSecretLogs(Collection $secretLogs)
    {
        $this->secretLogs = $secretLogs;
    }

    public function getHasDeviceSecrets(): ?bool
    {
        return $this->hasDeviceSecrets;
    }

    public function setHasDeviceSecrets(?bool $hasDeviceSecrets)
    {
        $this->hasDeviceSecrets = $hasDeviceSecrets;
    }

    public function getHashIdentifier(): ?string
    {
        return $this->hashIdentifier;
    }

    public function setHashIdentifier(?string $hashIdentifier)
    {
        $this->hashIdentifier = $hashIdentifier;
    }

    public function getLock(): ?DeviceLock
    {
        return $this->lock;
    }

    public function setLock(?DeviceLock $lock)
    {
        $this->lock = $lock;
    }
}
