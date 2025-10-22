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

namespace App\Service\Helper;

use App\Entity\Certificate;
use App\Entity\Device;
use App\Entity\DeviceEndpointDevice;
use App\Entity\User;
use App\Event\CertificatePostGenerateEvent;
use App\Event\CertificatePostRevokeEvent;
use App\Event\CertificatePreGenerateEvent;
use App\Event\CertificatePreRevokeEvent;
use App\Event\DeviceEndpointDevicePreRemoveEvent;
use App\Event\DeviceEndpointDeviceUpdatedEvent;
use App\Event\DevicePreRemoveEvent;
use App\Event\DeviceUpdatedEvent;
use App\Event\UserPreRemoveEvent;
use App\Event\UserUpdatedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait EventDispatcherTrait
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    #[Required]
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function dispatchDeviceUpdated(Device $device)
    {
        $this->eventDispatcher->dispatch(new DeviceUpdatedEvent($device));
    }

    public function dispatchDevicePreRemove(Device $device)
    {
        $this->eventDispatcher->dispatch(new DevicePreRemoveEvent($device));
    }

    public function dispatchDeviceEndpointDeviceUpdated(DeviceEndpointDevice $deviceEndpointDevice)
    {
        $this->eventDispatcher->dispatch(new DeviceEndpointDeviceUpdatedEvent($deviceEndpointDevice));
    }

    public function dispatchDeviceEndpointDevicePreRemove(DeviceEndpointDevice $deviceEndpointDevice)
    {
        $this->eventDispatcher->dispatch(new DeviceEndpointDevicePreRemoveEvent($deviceEndpointDevice));
    }

    public function dispatchUserUpdated(User $user)
    {
        $this->eventDispatcher->dispatch(new UserUpdatedEvent($user));
    }

    public function dispatchUserPreRemove(User $user)
    {
        $this->eventDispatcher->dispatch(new UserPreRemoveEvent($user));
    }

    public function dispatchCertificatePreGenerate(Certificate $certificate)
    {
        $this->eventDispatcher->dispatch(new CertificatePreGenerateEvent($certificate));
    }

    public function dispatchCertificatePostGenerate(Certificate $certificate)
    {
        $this->eventDispatcher->dispatch(new CertificatePostGenerateEvent($certificate));
    }

    public function dispatchCertificatePreRevoke(Certificate $certificate)
    {
        $this->eventDispatcher->dispatch(new CertificatePreRevokeEvent($certificate));
    }

    public function dispatchCertificatePostRevoke(Certificate $certificate)
    {
        $this->eventDispatcher->dispatch(new CertificatePostRevokeEvent($certificate));
    }
}
