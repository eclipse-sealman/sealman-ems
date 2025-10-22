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

use Carve\ApiBundle\Validator\Constraints as Assert;

class FlexEdgeModel
{
    /**
     * Serial number.
     */
    #[Assert\NotBlank(groups: ['flexEdgeCommunication', 'fieldSerialNumberRequired'])]
    #[Assert\Length(max: 255, groups: ['Default', 'authentication'])]
    private ?string $sn = null;

    /**
     * Firmware version.
     */
    #[Assert\Length(max: 255)]
    private ?string $ver = null;

    /**
     * Flex edge model.
     */
    #[Assert\NotBlank(groups: ['fieldModelRequired'])]
    #[Assert\Length(max: 255)]
    private ?string $mn = null;

    /**
     * Update status file id.
     */
    #[Assert\Length(max: 255)]
    private ?string $file_id = null;

    /**
     * Update status file status.
     */
    #[Assert\Length(max: 255)]
    private ?string $file_status = null;

    public function getSn(): ?string
    {
        return $this->sn;
    }

    public function setSn(?string $sn)
    {
        $this->sn = $sn;
    }

    public function getVer(): ?string
    {
        return $this->ver;
    }

    public function setVer(?string $ver)
    {
        $this->ver = $ver;
    }

    public function getMn(): ?string
    {
        return $this->mn;
    }

    public function setMn(?string $mn)
    {
        $this->mn = $mn;
    }

    public function getFileId(): ?string
    {
        return $this->file_id;
    }

    public function setFileId(?string $file_id)
    {
        $this->file_id = $file_id;
    }

    public function getFileStatus(): ?string
    {
        return $this->file_status;
    }

    public function setFileStatus(?string $file_status)
    {
        $this->file_status = $file_status;
    }
}
