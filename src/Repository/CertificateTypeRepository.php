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

namespace App\Repository;

use App\Entity\CertificateType;
use App\Enum\CertificateCategory;
use App\Enum\CertificateEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CertificateTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CertificateType::class);
    }

    public function findDeviceVpn(): ?CertificateType
    {
        return $this->findOneBy([
            'certificateCategory' => CertificateCategory::DEVICE_VPN,
            'certificateEntity' => CertificateEntity::DEVICE,
        ]);
    }

    public function findTechnicianVpn(): ?CertificateType
    {
        return $this->findOneBy([
            'certificateCategory' => CertificateCategory::TECHNICIAN_VPN,
            'certificateEntity' => CertificateEntity::USER,
        ]);
    }
}
