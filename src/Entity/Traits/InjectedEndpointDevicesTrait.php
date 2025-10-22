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

namespace App\Entity\Traits;

use Doctrine\Common\Collections\ArrayCollection;

trait InjectedEndpointDevicesTrait
{
    /**
     * Injected endpoint devices used by App\Form\Type\EndpointDevicesType.
     *
     * Lists endpoint devices that has NOT been send in the payload by user and should be injected due to lack of his access.
     */
    private ?ArrayCollection $injectedEndpointDevices = null;

    /**
     * Overridden endpoint devices used by App\Form\Type\EndpointDevicesType.
     *
     * Lists endpoint devices that HAS been send in the payload by user, but he lacks access to them. Data for them is overridden in payload.
     *
     * Used for better error messages in validator.
     */
    private ?ArrayCollection $overriddenEndpointDevices = null;

    public function getInjectedEndpointDevices(): ArrayCollection
    {
        if (null === $this->injectedEndpointDevices) {
            $this->injectedEndpointDevices = new ArrayCollection();
        }

        return $this->injectedEndpointDevices;
    }

    public function setInjectedEndpointDevices(ArrayCollection $injectedEndpointDevices)
    {
        $this->injectedEndpointDevices = $injectedEndpointDevices;
    }

    public function getOverriddenEndpointDevices(): ArrayCollection
    {
        if (null === $this->overriddenEndpointDevices) {
            $this->overriddenEndpointDevices = new ArrayCollection();
        }

        return $this->overriddenEndpointDevices;
    }

    public function setOverriddenEndpointDevices(ArrayCollection $overriddenEndpointDevices)
    {
        $this->overriddenEndpointDevices = $overriddenEndpointDevices;
    }
}
