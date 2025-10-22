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

namespace App\EventListener;

use App\Entity\DeviceTypeCertificateType;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postLoad, method: 'postLoad', entity: DeviceTypeCertificateType::class)]
class DeviceTypeCertificateTypePostLoadListener
{
    public function postLoad(DeviceTypeCertificateType $deviceTypeCertificateType, LifecycleEventArgs $event): void
    {
        $deviceTypeCertificateType->setIsCertificateTypeAvailable(
                $deviceTypeCertificateType->getDeviceType()->getHasCertificates() &&
                $deviceTypeCertificateType->getCertificateType()->getIsAvailable()
            );
    }
}
