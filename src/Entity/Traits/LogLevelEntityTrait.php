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

use App\Enum\LogLevel;
use Carve\ApiBundle\Attribute\Export\ExportEnumPrefix;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait LogLevelEntityTrait
{
    #[Groups(['logLevel'])]
    #[ExportEnumPrefix('enum.common.logLevel.')]
    #[ORM\Column(type: Types::STRING, enumType: LogLevel::class)]
    private ?LogLevel $logLevel = null;

    public function getLogLevel(): ?LogLevel
    {
        return $this->logLevel;
    }

    public function setLogLevel(?LogLevel $logLevel)
    {
        $this->logLevel = $logLevel;
    }
}
