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

namespace App\Service\Trait;

use App\Entity\Certificate;
use App\Entity\CertificateType;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\DeviceTypeCertificateType;
use App\Entity\User;
use App\Enum\CertificateCategory;
use App\Enum\CertificateEntity;
use App\Enum\PkiType;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use Doctrine\Common\Collections\Collection;

// Trait that helps with certificateTypes handling
trait CertificateTypeHelperTrait
{
    use EntityManagerTrait;
    use ConfigurationManagerTrait;

    protected function getDeviceVpnCertificateType(): ?CertificateType
    {
        return $this->getRepository(CertificateType::class)->findDeviceVpn();
    }

    protected function getTechnicianVpnCertificateType(): ?CertificateType
    {
        return $this->getRepository(CertificateType::class)->findTechnicianVpn();
    }

    protected function getCertificateTypes(): array|Collection
    {
        $queryBuilder = $this->getRepository(CertificateType::class)->createQueryBuilder('ct');

        return $queryBuilder->getQuery()->getResult();
    }

    protected function getCertificateTypeByCertificateCategory(CertificateCategory $certificateCategory, CertificateEntity $certificateEntity): ?CertificateType
    {
        $queryBuilder = $this->getRepository(CertificateType::class)->createQueryBuilder('ct');
        $queryBuilder->andWhere('ct.certificateCategory = :certificateCategory');
        $queryBuilder->setParameter('certificateCategory', $certificateCategory);
        $queryBuilder->andWhere('ct.certificateEntity = :certificateEntity');
        $queryBuilder->setParameter('certificateEntity', $certificateEntity);
        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    protected function getCertificateTypesByCertificateCategories(array $certificateCategories, CertificateEntity $certificateEntity): array|Collection
    {
        $queryBuilder = $this->getRepository(CertificateType::class)->createQueryBuilder('ct');
        $queryBuilder->andWhere('ct.certificateCategory IN (:certificateCategories)');
        $queryBuilder->setParameter('certificateCategories', $certificateCategories);
        $queryBuilder->andWhere('ct.certificateEntity = :certificateEntity');
        $queryBuilder->setParameter('certificateEntity', $certificateEntity);

        return $queryBuilder->getQuery()->getResult();
    }

    protected function getCertificateTypesByCertificateEntity(CertificateEntity $certificateEntity): array|Collection
    {
        $queryBuilder = $this->getRepository(CertificateType::class)->createQueryBuilder('ct');
        $queryBuilder->andWhere('ct.certificateEntity = :certificateEntity');
        $queryBuilder->setParameter('certificateEntity', $certificateEntity);

        return $queryBuilder->getQuery()->getResult();
    }

    protected function hasDeviceTypeDeviceVpnCertificate(DeviceType $deviceType): bool
    {
        if (!$deviceType->getCertificateTypes()) {
            return false;
        }

        foreach ($deviceType->getCertificateTypes() as $deviceTypeCertificateType) {
            if (CertificateCategory::DEVICE_VPN == $deviceTypeCertificateType->getCertificateType()->getCertificateCategory()) {
                return true;
            }
        }

        return false;
    }

    protected function getDeviceTypeCertificateTypeByType(DeviceType $deviceType, CertificateType $certificateType): ?DeviceTypeCertificateType
    {
        foreach ($deviceType->getCertificateTypes() as $deviceTypeCertificateType) {
            if ($deviceTypeCertificateType->getCertificateType() == $certificateType) {
                return $deviceTypeCertificateType;
            }
        }

        return null;
    }

    /**
     * Methods gets Certificate Device or User of provided CertificateType or null.
     * $target and $certificateType can be null for convenient use - if any of parameters is null, null will be returned.
     */
    public function getCertificateByType(Device|User|null $target, ?CertificateType $certificateType): ?Certificate
    {
        if (!$target) {
            return null;
        }

        if (!$certificateType) {
            return null;
        }

        foreach ($target->getCertificates() as $certificate) {
            if ($certificateType == $certificate->getCertificateType()) {
                return $certificate;
            }
        }

        return null;
    }

    protected function getVpnCertificate(Device|User|null $target): ?Certificate
    {
        if ($target instanceof Device) {
            return $this->getCertificateByType($target, $this->getDeviceVpnCertificateType());
        }
        if ($target instanceof User) {
            return $this->getCertificateByType($target, $this->getTechnicianVpnCertificateType());
        }

        return null;
    }

    protected function getAvailableCertificateTypes(Device|User $object): array
    {
        if ($object instanceof Device) {
            $availableCertificateTypes = [];
            foreach ($object->getDeviceType()->getCertificateTypes() as $deviceTypeCertificateType) {
                if ($deviceTypeCertificateType->getIsCertificateTypeAvailable()) {
                    if (CertificateEntity::DEVICE === $deviceTypeCertificateType->getCertificateType()->getCertificateEntity()) {
                        $availableCertificateTypes[] = $deviceTypeCertificateType->getCertificateType();
                    }
                }
            }

            return $availableCertificateTypes;
        }

        if ($object instanceof User) {
            return $this->getAvailableCertificateTypesForCertificateEntity(CertificateEntity::USER);
        }

        return [];
    }

    protected function getAvailableCertificateTypesForCertificateEntity(CertificateEntity $certificateEntity): array|Collection
    {
        $availableCertificateTypes = [];

        foreach ($this->getCertificateTypesByCertificateEntity($certificateEntity) as $certificateType) {
            if ($certificateType->getIsAvailable()) {
                $availableCertificateTypes[] = $certificateType;
            }
        }

        return $availableCertificateTypes;
    }

    // Method is to be used just to make sure that previous validations were correct
    protected function validateCertificateEntity(CertificateType $certificateType, Device|User $target): void
    {
        // Exception here means that something wasn't validated correctly - development issue occured

        if ($target instanceof Device && CertificateEntity::DEVICE !== $certificateType->getCertificateEntity()) {
            throw new \Exception('CertificateType not supported by '.Device::class);
        }

        if ($target instanceof User && CertificateEntity::USER !== $certificateType->getCertificateEntity()) {
            throw new \Exception('CertificateType not supported by '.User::class);
        }
    }

    // Method calculates if certificate type is available based on current system and license state
    // Method moved to this trait because it's used in CertificateTypeDeny and CertificateTypePostLoadListener
    protected function getCertificateTypeAvailableDeny(CertificateType $certificateType): ?string
    {
        if (!$certificateType->getEnabled()) {
            return 'disabled';
        }

        // Custom certificate types that require VPN license
        if (in_array($certificateType->getCertificateCategory(), [CertificateCategory::DEVICE_VPN, CertificateCategory::TECHNICIAN_VPN])) {
            if ($this->configurationManager->isVpnSecuritySuiteBlocked()) {
                return 'vpnLicenseRequired';
            }
        }

        if (PkiType::SCEP == $certificateType->getPkiType()) {
            if ($this->configurationManager->isScepBlocked()) {
                return 'scepLicenseRequired';
            }
            if (!$this->configurationManager->isScepForCertificateTypeAvailable($certificateType)) {
                return 'invalidScepConfiguration';
            }
        }

        return null;
    }

    // Method checks if certificateType's PKI functionality is available
    protected function isCertificateTypePkiAvailable(CertificateType $certificateType): bool
    {
        switch ($certificateType->getPkiType()) {
            case PkiType::SCEP:
                return $this->configurationManager->isScepForCertificateTypeAvailable($certificateType);
            case PkiType::NONE:
            default:
                return false;
        }
    }
}
