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

namespace App\Model;

use Symfony\Component\Serializer\Annotation\Groups;

class SgGatewayResponseModel extends ResponseModel
{
    /**
     * Serial number.
     */
    #[Groups(['sgGateway:register', 'sgGateway:configuration'])]
    private ?string $serialNumber = null;

    /**
     * This is just proof of concept. Configuration can be now anything.
     *
     * Configuration
     */
    #[Groups(['sgGateway:configuration'])]
    private null|string|SerializableJson $configuration = null;

    /**
     *  Error information.
     */
    #[Groups(['sgGateway:register', 'sgGateway:configuration'])]
    private ?string $error = null;

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(?string $serialNumber)
    {
        $this->serialNumber = $serialNumber;
    }

    public function getConfiguration(): null|string|SerializableJson
    {
        return $this->configuration;
    }

    public function setConfiguration(null|string|SerializableJson $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error)
    {
        $this->error = $error;
    }
}
