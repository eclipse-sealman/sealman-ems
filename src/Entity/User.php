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
use App\Entity\Traits\VpnClientEntityInterface;
use App\Entity\Traits\VpnClientEntityTrait;
use App\Entity\Traits\VpnEntityInteface;
use App\Model\AuditableInterface;
use App\Model\UseableCertificate;
use App\Validator\Constraints\User as UserValidator;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Carve\ApiBundle\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity]
#[UniqueEntity('username')]
#[UniqueEntity('apiKey')]
#[UserValidator(groups: ['user:webUser'])]
class User implements UserInterface, DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, PasswordAuthenticatedUserInterface, PasswordHasherAwareInterface, LegacyPasswordAuthenticatedUserInterface, VpnClientEntityInterface, VpnEntityInteface, AuditableInterface
{
    use DenyTrait;
    use VpnClientEntityTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[Groups(['user:public', 'deviceAuthentication:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $enabled = false;

    #[Groups(['user:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $enabledExpireAt = null;

    #[Groups(['user:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $tooManyFailedLoginAttempts = false;

    #[Groups(['user:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $roleAdmin = false;

    #[Groups(['user:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $roleSmartems = false;

    #[Groups(['user:adminVpn', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $roleVpn = false;

    #[Groups(['user:adminVpn', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $roleVpnEndpointDevices = false;

    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $roleDevice = false;

    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $roleDeviceSecretCredential = false;

    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $roleDeviceX509Credential = false;

    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $roleSystem = false;

    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $roleLegacyApi = false;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $apiKey = null;

    #[Assert\NotBlank(groups: ['user:webUser', 'deviceAuthentication:common'])]
    #[Assert\Length(max: 255)]
    #[Groups(['user:public', 'deviceAuthentication:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $username = null;

    #[Assert\NotBlank(groups: ['deviceAuthentication:common'])]
    #[Groups([AuditableInterface::ENCRYPTED_GROUP])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $password = null;

    #[Groups(['deviceAuthentication:public'])]
    #[SerializedName('password')]
    private ?string $decryptedPassword = null;

    #[Groups(['user:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $disablePasswordExpire = false;

    #[Groups([AuditableInterface::GROUP])]
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTime $passwordUpdatedAt = null;

    #[Groups([AuditableInterface::ENCRYPTED_GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $salt = null;

    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $failedLoginAttempts = 0;

    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $failedLoginAttemptsAt = null;

    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $lastLoginAt = null;

    #[Groups([AuditableInterface::ENCRYPTED_GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $totpSecret = null;

    #[Groups(['user:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $totpEnabled = false;

    // Not mapped property for TOTP authentication and verification process
    private ?bool $totpRequired = true;

    #[Groups(['user:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $radiusUser = false;

    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $radiusUserAllDevicesAccess = false;

    #[Groups(['user:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $ssoUser = false;

    #[Groups(['user:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $ssoName = null;

    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $ssoLogoutAt = null;

    #[Groups([AuditableInterface::ENCRYPTED_GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $ssoSessionId = null;

    #[Groups(['user:public', 'device:public', AuditableInterface::GROUP])]
    #[ORM\ManyToMany(inversedBy: 'users', targetEntity: AccessTag::class)]
    private Collection $accessTags;

    /**
     * VPN connections.
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: VpnConnection::class)]
    private Collection $vpnConnections;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserDeviceType::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['deviceType' => 'ASC'])]
    private Collection $userDeviceTypes;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: VpnLog::class)]
    private Collection $vpnLogs;

    /**
     * User certificates.
     */
    #[Groups(['certificate:admin', 'certificate:vpn', 'certificate:smartems'])]
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Certificate::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $certificates;

    /**
     * Helper field for UserCreateType.
     */
    private ?string $plainPassword = null;

    /**
     * Helper field for DeviceAuthenticationType.
     */
    #[Assert\Count(min: 1, groups: ['deviceAuthentication:common'])]
    #[Assert\Valid(groups: ['deviceAuthentication:common'])]
    private ?Collection $deviceTypes;

    /**
     * Helper field for UserCreateType.
     */
    private ?string $plainPasswordRepeat = null;

    /**
     * Helper field for certificates deny handling. Using UsableCertificate model.
     */
    #[Groups(['certificate:admin', 'certificate:vpn', 'certificate:smartems', 'certificate:private'])]
    #[OA\Property(type: 'array', items: new OA\Items(ref: new NA\Model(type: UseableCertificate::class)))]
    private Collection $useableCertificates;

    /**
     * Helper field for handling certificates behaviours values provided by user. Using UsableCertificate model.
     */
    #[Assert\Valid(groups: ['user:certificateBehaviours'])]
    private Collection $certificateBehaviours;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        if ($this->getSsoUser() && $this->getSsoName()) {
            return $this->getSsoName();
        }

        return (string) $this->getUsername();
    }

    #[Groups(['user:public'])]
    #[SerializedName('isEnabled')]
    public function getIsEnabled(): bool
    {
        if (!$this->getEnabled()) {
            return false;
        }

        $enabledExpireAt = $this->getEnabledExpireAt();
        if (!$enabledExpireAt) {
            return true;
        }

        $now = new \DateTime();

        return $enabledExpireAt >= $now ? true : false;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->getUsername();
    }

    #[Groups(['user:public'])]
    public function getTotpSecretGenerated(): bool
    {
        return $this->totpSecret ? true : false;
    }

    #[Groups(['deviceAuthentication:public'])]
    public function getDeviceTypes(): ?Collection
    {
        if (isset($this->deviceTypes)) {
            // if its allready set in edit form
            return $this->deviceTypes;
        }

        $deviceTypes = new ArrayCollection();
        foreach ($this->getUserDeviceTypes() as $userDeviceType) {
            $deviceTypes->add($userDeviceType->getDeviceType());
        }

        return $deviceTypes;
    }

    public function getPasswordHasherName(): ?string
    {
        if ($this->getRoleDeviceSecretCredential()) {
            return 'no_password_hasher';
        }

        if ($this->getRoleDeviceX509Credential()) {
            return 'no_password_hasher';
        }

        if ($this->getRoleDevice()) {
            return 'aes_encoder';
        }

        if ($this->getRadiusUser()) {
            return 'radius_encoder';
        }

        if ($this->getSsoUser()) {
            return 'sso_hasher';
        }

        // Fallback to default encoder
        return null;
    }

    public function getRoles(): array
    {
        // One role needs to exist
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    public function addAccessTag(AccessTag $accessTag)
    {
        if (!$this->accessTags->contains($accessTag)) {
            $this->accessTags->add($accessTag);
            $accessTag->addUser($this);
        }
    }

    public function removeAccessTag(AccessTag $accessTag)
    {
        if ($this->accessTags->contains($accessTag)) {
            $this->accessTags->removeElement($accessTag);
            $accessTag->removeUser($this);
        }
    }

    public function addUserDeviceType(UserDeviceType $userDeviceType)
    {
        if (!$this->userDeviceTypes->contains($userDeviceType)) {
            $this->userDeviceTypes[] = $userDeviceType;
            $userDeviceType->setUser($this);
        }
    }

    public function removeUserDeviceType(UserDeviceType $userDeviceType)
    {
        if ($this->userDeviceTypes->contains($userDeviceType)) {
            $this->userDeviceTypes->removeElement($userDeviceType);
        }
    }

    public function addCertificate(Certificate $certificate)
    {
        if (!$this->certificates->contains($certificate)) {
            $this->certificates[] = $certificate;
            $certificate->setUser($this);
        }
    }

    public function removeCertificate(Certificate $certificate)
    {
        if ($this->certificates->removeElement($certificate)) {
            if ($certificate->getUser() === $this) {
                $certificate->setUser(null);
            }
        }
    }

    public function __construct()
    {
        $this->accessTags = new ArrayCollection();
        $this->vpnConnections = new ArrayCollection();
        $this->userDeviceTypes = new ArrayCollection();
        $this->vpnLogs = new ArrayCollection();
        $this->deviceTypes = new ArrayCollection();
        $this->certificates = new ArrayCollection();
        $this->useableCertificates = new ArrayCollection();
        $this->certificateBehaviours = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getRoleAdmin(): ?bool
    {
        return $this->roleAdmin;
    }

    public function setRoleAdmin(?bool $roleAdmin)
    {
        $this->roleAdmin = $roleAdmin;
    }

    public function getRoleDevice(): ?bool
    {
        return $this->roleDevice;
    }

    public function setRoleDevice(?bool $roleDevice)
    {
        $this->roleDevice = $roleDevice;
    }

    public function getRoleSystem(): ?bool
    {
        return $this->roleSystem;
    }

    public function setRoleSystem(?bool $roleSystem)
    {
        $this->roleSystem = $roleSystem;
    }

    public function getRoleLegacyApi(): ?bool
    {
        return $this->roleLegacyApi;
    }

    public function setRoleLegacyApi(?bool $roleLegacyApi)
    {
        $this->roleLegacyApi = $roleLegacyApi;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username)
    {
        $this->username = $username;
    }

    public function getDisablePasswordExpire(): ?bool
    {
        return $this->disablePasswordExpire;
    }

    public function setDisablePasswordExpire(?bool $disablePasswordExpire)
    {
        $this->disablePasswordExpire = $disablePasswordExpire;
    }

    public function getPasswordUpdatedAt(): ?\DateTime
    {
        return $this->passwordUpdatedAt;
    }

    public function setPasswordUpdatedAt(?\DateTime $passwordUpdatedAt)
    {
        $this->passwordUpdatedAt = $passwordUpdatedAt;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setSalt(?string $salt)
    {
        $this->salt = $salt;
    }

    public function getFailedLoginAttempts(): ?int
    {
        return $this->failedLoginAttempts;
    }

    public function setFailedLoginAttempts(?int $failedLoginAttempts)
    {
        $this->failedLoginAttempts = $failedLoginAttempts;
    }

    public function getFailedLoginAttemptsAt(): ?\DateTime
    {
        return $this->failedLoginAttemptsAt;
    }

    public function setFailedLoginAttemptsAt(?\DateTime $failedLoginAttemptsAt)
    {
        $this->failedLoginAttemptsAt = $failedLoginAttemptsAt;
    }

    public function getLastLoginAt(): ?\DateTime
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTime $lastLoginAt)
    {
        $this->lastLoginAt = $lastLoginAt;
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $totpSecret)
    {
        $this->totpSecret = $totpSecret;
    }

    public function getTotpRequired(): ?bool
    {
        return $this->totpRequired;
    }

    public function setTotpRequired(?bool $totpRequired)
    {
        $this->totpRequired = $totpRequired;
    }

    public function getAccessTags(): Collection
    {
        return $this->accessTags;
    }

    public function setAccessTags(Collection $accessTags)
    {
        $this->accessTags = $accessTags;
    }

    public function getUserDeviceTypes(): Collection
    {
        return $this->userDeviceTypes;
    }

    public function setUserDeviceTypes(Collection $userDeviceTypes)
    {
        $this->userDeviceTypes = $userDeviceTypes;
    }

    public function getVpnLogs(): Collection
    {
        return $this->vpnLogs;
    }

    public function setVpnLogs(Collection $vpnLogs)
    {
        $this->vpnLogs = $vpnLogs;
    }

    public function getTotpEnabled(): ?bool
    {
        return $this->totpEnabled;
    }

    public function setTotpEnabled(?bool $totpEnabled)
    {
        $this->totpEnabled = $totpEnabled;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled)
    {
        $this->enabled = $enabled;
    }

    public function getTooManyFailedLoginAttempts(): ?bool
    {
        return $this->tooManyFailedLoginAttempts;
    }

    public function setTooManyFailedLoginAttempts(?bool $tooManyFailedLoginAttempts)
    {
        $this->tooManyFailedLoginAttempts = $tooManyFailedLoginAttempts;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword)
    {
        $this->plainPassword = $plainPassword;
    }

    public function getPlainPasswordRepeat(): ?string
    {
        return $this->plainPasswordRepeat;
    }

    public function setPlainPasswordRepeat(?string $plainPasswordRepeat)
    {
        $this->plainPasswordRepeat = $plainPasswordRepeat;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password)
    {
        $this->password = $password;
    }

    public function getVpnConnections(): Collection
    {
        return $this->vpnConnections;
    }

    public function setVpnConnections(Collection $vpnConnections)
    {
        $this->vpnConnections = $vpnConnections;
    }

    public function setDeviceTypes(?Collection $deviceTypes)
    {
        $this->deviceTypes = $deviceTypes;
    }

    public function getRoleSmartems(): ?bool
    {
        return $this->roleSmartems;
    }

    public function setRoleSmartems(?bool $roleSmartems)
    {
        $this->roleSmartems = $roleSmartems;
    }

    public function getRoleVpn(): ?bool
    {
        return $this->roleVpn;
    }

    public function setRoleVpn(?bool $roleVpn)
    {
        $this->roleVpn = $roleVpn;
    }

    public function getRadiusUserAllDevicesAccess(): ?bool
    {
        return $this->radiusUserAllDevicesAccess;
    }

    public function setRadiusUserAllDevicesAccess(?bool $radiusUserAllDevicesAccess)
    {
        $this->radiusUserAllDevicesAccess = $radiusUserAllDevicesAccess;
    }

    public function getRadiusUser(): ?bool
    {
        return $this->radiusUser;
    }

    public function setRadiusUser(?bool $radiusUser)
    {
        $this->radiusUser = $radiusUser;
    }

    public function getEnabledExpireAt(): ?\DateTime
    {
        return $this->enabledExpireAt;
    }

    public function setEnabledExpireAt(?\DateTime $enabledExpireAt)
    {
        $this->enabledExpireAt = $enabledExpireAt;
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

    public function getSsoLogoutAt(): ?\DateTime
    {
        return $this->ssoLogoutAt;
    }

    public function setSsoLogoutAt(?\DateTime $ssoLogoutAt)
    {
        $this->ssoLogoutAt = $ssoLogoutAt;
    }

    public function getSsoSessionId(): ?string
    {
        return $this->ssoSessionId;
    }

    public function setSsoSessionId(?string $ssoSessionId)
    {
        $this->ssoSessionId = $ssoSessionId;
    }

    public function getSsoName(): ?string
    {
        return $this->ssoName;
    }

    public function setSsoName(?string $ssoName)
    {
        $this->ssoName = $ssoName;
    }

    public function getSsoUser(): ?bool
    {
        return $this->ssoUser;
    }

    public function setSsoUser(?bool $ssoUser)
    {
        $this->ssoUser = $ssoUser;
    }

    public function getCertificateBehaviours(): Collection
    {
        return $this->certificateBehaviours;
    }

    public function setCertificateBehaviours(Collection $certificateBehaviours)
    {
        $this->certificateBehaviours = $certificateBehaviours;
    }

    public function getRoleDeviceSecretCredential(): ?bool
    {
        return $this->roleDeviceSecretCredential;
    }

    public function setRoleDeviceSecretCredential(?bool $roleDeviceSecretCredential)
    {
        $this->roleDeviceSecretCredential = $roleDeviceSecretCredential;
    }

    public function getRoleDeviceX509Credential(): ?bool
    {
        return $this->roleDeviceX509Credential;
    }

    public function setRoleDeviceX509Credential(?bool $roleDeviceX509Credential)
    {
        $this->roleDeviceX509Credential = $roleDeviceX509Credential;
    }

    public function getDecryptedPassword(): ?string
    {
        return $this->decryptedPassword;
    }

    public function setDecryptedPassword(?string $decryptedPassword)
    {
        $this->decryptedPassword = $decryptedPassword;
    }

    public function getRoleVpnEndpointDevices(): ?bool
    {
        return $this->roleVpnEndpointDevices;
    }

    public function setRoleVpnEndpointDevices(?bool $roleVpnEndpointDevices)
    {
        $this->roleVpnEndpointDevices = $roleVpnEndpointDevices;
    }
}
