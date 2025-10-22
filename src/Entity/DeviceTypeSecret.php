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
use App\Enum\SecretValueBehaviour;
use App\Model\AuditableInterface;
use App\Validator\Constraints\DeviceTypeSecret as DeviceTypeSecretValidator;
use App\Validator\Constraints\SecretVariablePrefix as SecretVariablePrefixValidator;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[DeviceTypeSecretValidator(groups: ['deviceTypeSecret:common'])]
#[SecretVariablePrefixValidator(groups: ['deviceTypeSecret:common'])]
class DeviceTypeSecret implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, AuditableInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Name of the secret - used for UI and logs.
     */
    #[Groups(['deviceTypeSecret:public', 'deviceSecret:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceTypeSecret:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * Enable using secret as variable.
     */
    #[Groups(['deviceTypeSecret:public', 'deviceSecret:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $useAsVariable = false;

    /**
     * Variable name prefix of this secret. Suffix will define encoding of secret value.
     */
    #[Groups(['deviceTypeSecret:public', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'useAsVariable', groups: ['deviceTypeSecret:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $variableNamePrefix = null;

    /**
     * Description of secret - for convenience in UI.
     */
    #[Groups(['deviceTypeSecret:public', 'deviceSecret:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceTypeSecret:common'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Secret value behaviour.
     */
    #[Groups(['deviceTypeSecret:public', 'deviceSecret:public', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'useAsVariable', groups: ['deviceTypeSecret:common'])]
    #[ORM\Column(type: Types::STRING, enumType: SecretValueBehaviour::class, nullable: true)]
    private ?SecretValueBehaviour $secretValueBehaviour = null;

    /**
     * Secret value renew after days.
     */
    #[Groups(['deviceTypeSecret:public', 'deviceSecret:public', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 1, groups: ['deviceTypeSecret:common'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $secretValueRenewAfterDays = null;

    /**
     * Allow users to manually enforce renewal on next device communication.
     */
    #[Groups(['deviceTypeSecret:public', 'deviceSecret:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $manualForceRenewal = false;

    /**
     * Allow users to manually edit secret value.
     */
    #[Groups(['deviceTypeSecret:public', 'deviceSecret:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $manualEdit = false;

    /**
     * Enable reminder for manual secret value renewal.
     */
    #[Groups(['deviceTypeSecret:public', 'deviceSecret:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $manualEditRenewReminder = false;

    /**
     * Reminder for manual secret value renewal in days.
     */
    #[Groups(['deviceTypeSecret:public', 'deviceSecret:public', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 1, groups: ['deviceTypeSecret:common'])]
    #[Assert\NotBlankOnTrue(propertyPath: 'manualEditRenewReminder', groups: ['deviceTypeSecret:common'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $manualEditRenewReminderAfterDays = null;

    /**
     * Secret value minimum length.
     */
    #[Groups(['deviceTypeSecret:public', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 1, groups: ['deviceTypeSecret:common'])]
    #[Assert\NotBlank(groups: ['deviceTypeSecret:common'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $secretMinimumLength = 8;

    /**
     * Secret value minimum digits amount.
     */
    #[Groups(['deviceTypeSecret:public', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['deviceTypeSecret:common'])]
    #[Assert\NotBlank(groups: ['deviceTypeSecret:common'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $secretDigitsAmount = 1;

    /**
     * Secret value minimum lowercase letters amount.
     */
    #[Groups(['deviceTypeSecret:public', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['deviceTypeSecret:common'])]
    #[Assert\NotBlank(groups: ['deviceTypeSecret:common'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $secretLowercaseLettersAmount = 1;

    /**
     * Secret value minimum uppercase letters amount.
     */
    #[Groups(['deviceTypeSecret:public', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['deviceTypeSecret:common'])]
    #[Assert\NotBlank(groups: ['deviceTypeSecret:common'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $secretUppercaseLettersAmount = 1;

    /**
     * Secret value minimum special characters amount.
     */
    #[Groups(['deviceTypeSecret:public', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['deviceTypeSecret:common'])]
    #[Assert\NotBlank(groups: ['deviceTypeSecret:common'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $secretSpecialCharactersAmount = 1;

    /**
     * Access tags that allow user with device management permissions to see this secret (if user also have access to device).
     */
    #[Groups(['deviceTypeSecret:public', AuditableInterface::GROUP])]
    #[ORM\ManyToMany(inversedBy: 'deviceTypeSecrets', targetEntity: AccessTag::class)]
    private Collection $accessTags;

    /**
     * Device type containing this secret.
     */
    #[Groups(['deviceTypeSecret:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class, inversedBy: 'deviceTypeSecrets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DeviceType $deviceType = null;

    /**
     * Device secrets.
     */
    #[ORM\OneToMany(mappedBy: 'deviceTypeSecret', targetEntity: DeviceSecret::class)]
    private Collection $deviceSecrets;

    /**
     * Secret logs.
     */
    #[ORM\OneToMany(mappedBy: 'deviceTypeSecret', targetEntity: SecretLog::class)]
    private Collection $secretLogs;

    #[ORM\OneToMany(mappedBy: 'deviceTypeSecretCredential', targetEntity: DeviceType::class)]
    private Collection $deviceTypeSecretCredentials;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    public function addAccessTag(AccessTag $accessTag)
    {
        if (!$this->accessTags->contains($accessTag)) {
            $this->accessTags->add($accessTag);
            $accessTag->addDeviceTypeSecret($this);
        }
    }

    public function removeAccessTag(AccessTag $accessTag)
    {
        if ($this->accessTags->contains($accessTag)) {
            $this->accessTags->removeElement($accessTag);
            $accessTag->removeDeviceTypeSecret($this);
        }
    }

    public function __construct()
    {
        $this->accessTags = new ArrayCollection();
        $this->deviceSecrets = new ArrayCollection();
        $this->secretLogs = new ArrayCollection();
        $this->deviceTypeSecretCredentials = new ArrayCollection();
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

    public function getVariableNamePrefix(): ?string
    {
        return $this->variableNamePrefix;
    }

    public function setVariableNamePrefix(?string $variableNamePrefix)
    {
        $this->variableNamePrefix = $variableNamePrefix;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
    }

    public function getSecretMinimumLength(): ?int
    {
        return $this->secretMinimumLength;
    }

    public function setSecretMinimumLength(?int $secretMinimumLength)
    {
        $this->secretMinimumLength = $secretMinimumLength;
    }

    public function getSecretDigitsAmount(): ?int
    {
        return $this->secretDigitsAmount;
    }

    public function setSecretDigitsAmount(?int $secretDigitsAmount)
    {
        $this->secretDigitsAmount = $secretDigitsAmount;
    }

    public function getSecretSpecialCharactersAmount(): ?int
    {
        return $this->secretSpecialCharactersAmount;
    }

    public function setSecretSpecialCharactersAmount(?int $secretSpecialCharactersAmount)
    {
        $this->secretSpecialCharactersAmount = $secretSpecialCharactersAmount;
    }

    public function getAccessTags(): Collection
    {
        return $this->accessTags;
    }

    public function setAccessTags(Collection $accessTags)
    {
        $this->accessTags = $accessTags;
    }

    public function getDeviceType(): ?DeviceType
    {
        return $this->deviceType;
    }

    public function setDeviceType(?DeviceType $deviceType)
    {
        $this->deviceType = $deviceType;
    }

    public function getSecretLogs(): Collection
    {
        return $this->secretLogs;
    }

    public function setSecretLogs(Collection $secretLogs)
    {
        $this->secretLogs = $secretLogs;
    }

    public function getDeviceSecrets(): Collection
    {
        return $this->deviceSecrets;
    }

    public function setDeviceSecrets(Collection $deviceSecrets)
    {
        $this->deviceSecrets = $deviceSecrets;
    }

    public function getUseAsVariable(): ?bool
    {
        return $this->useAsVariable;
    }

    public function setUseAsVariable(?bool $useAsVariable)
    {
        $this->useAsVariable = $useAsVariable;
    }

    public function getSecretUppercaseLettersAmount(): ?int
    {
        return $this->secretUppercaseLettersAmount;
    }

    public function setSecretUppercaseLettersAmount(?int $secretUppercaseLettersAmount)
    {
        $this->secretUppercaseLettersAmount = $secretUppercaseLettersAmount;
    }

    public function getSecretLowercaseLettersAmount(): ?int
    {
        return $this->secretLowercaseLettersAmount;
    }

    public function setSecretLowercaseLettersAmount(?int $secretLowercaseLettersAmount)
    {
        $this->secretLowercaseLettersAmount = $secretLowercaseLettersAmount;
    }

    public function getDeviceTypeSecretCredentials(): Collection
    {
        return $this->deviceTypeSecretCredentials;
    }

    public function setDeviceTypeSecretCredentials(Collection $deviceTypeSecretCredentials)
    {
        $this->deviceTypeSecretCredentials = $deviceTypeSecretCredentials;
    }

    public function getSecretValueBehaviour(): ?SecretValueBehaviour
    {
        return $this->secretValueBehaviour;
    }

    public function setSecretValueBehaviour(?SecretValueBehaviour $secretValueBehaviour)
    {
        $this->secretValueBehaviour = $secretValueBehaviour;
    }

    public function getSecretValueRenewAfterDays(): ?int
    {
        return $this->secretValueRenewAfterDays;
    }

    public function setSecretValueRenewAfterDays(?int $secretValueRenewAfterDays)
    {
        $this->secretValueRenewAfterDays = $secretValueRenewAfterDays;
    }

    public function getManualEdit(): ?bool
    {
        return $this->manualEdit;
    }

    public function setManualEdit(?bool $manualEdit)
    {
        $this->manualEdit = $manualEdit;
    }

    public function getManualEditRenewReminder(): ?bool
    {
        return $this->manualEditRenewReminder;
    }

    public function setManualEditRenewReminder(?bool $manualEditRenewReminder)
    {
        $this->manualEditRenewReminder = $manualEditRenewReminder;
    }

    public function getManualEditRenewReminderAfterDays(): ?int
    {
        return $this->manualEditRenewReminderAfterDays;
    }

    public function setManualEditRenewReminderAfterDays(?int $manualEditRenewReminderAfterDays)
    {
        $this->manualEditRenewReminderAfterDays = $manualEditRenewReminderAfterDays;
    }

    public function getManualForceRenewal(): ?bool
    {
        return $this->manualForceRenewal;
    }

    public function setManualForceRenewal(?bool $manualForceRenewal)
    {
        $this->manualForceRenewal = $manualForceRenewal;
    }
}
