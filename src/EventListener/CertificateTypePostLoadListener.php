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

use App\Entity\CertificateType;
use App\Service\Trait\CertificateTypeHelperTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postLoad, method: 'postLoad', entity: CertificateType::class)]
class CertificateTypePostLoadListener
{
    use CertificateTypeHelperTrait;

    public function postLoad(CertificateType $certificateType, LifecycleEventArgs $event): void
    {
        $certificateType->setIsAvailable(null === $this->getCertificateTypeAvailableDeny($certificateType));
    }
}
