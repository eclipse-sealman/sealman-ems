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

use App\Entity\DeviceType;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postLoad, method: 'postLoad', entity: DeviceType::class)]
class DeviceTypePostLoadListener
{
    use ConfigurationManagerTrait;
    use DeviceCommunicationFactoryTrait;

    public function postLoad(DeviceType $deviceType, LifecycleEventArgs $event): void
    {
        $deviceType->setIsVpnAvailable($deviceType->getHasVpn() && $this->configurationManager->isVpnSecuritySuiteAvailable());
        $deviceType->setIsMasqueradeAvailable($deviceType->getHasMasquerade() && $this->configurationManager->isVpnSecuritySuiteAvailable());
        $deviceType->setIsEndpointDevicesAvailable($deviceType->getHasEndpointDevices() && $this->configurationManager->isVpnSecuritySuiteAvailable());
        $deviceType->setIsDeviceToNetworkConnectionAvailable($deviceType->getHasDeviceToNetworkConnection() && $this->configurationManager->isVpnSecuritySuiteAvailable());

        if ($deviceType->getEnabled()) {
            $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($deviceType);
            $deviceType->setIsAvailable($communicationProcedure->isDeviceTypeValid());
        } else {
            $deviceType->setIsAvailable(false);
        }
    }
}
