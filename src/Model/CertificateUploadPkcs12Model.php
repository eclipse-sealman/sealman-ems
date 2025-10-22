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

use App\Entity\Certificate;
use Carve\ApiBundle\Validator\Constraints as Assert;

class CertificateUploadPkcs12Model
{
    /**
     * PKCS#12 TUS uploaded file.
     */
    #[Assert\NotBlank(groups: ['certificate:common'])]
    private ?string $pkcs12 = null;

    /**
     * PKCS#12 password.
     */
    private ?string $password = null;

    /**
     * Certificate object - setup by controller.
     */
    private ?Certificate $certificateObject = null;

    public function getPkcs12(): ?string
    {
        return $this->pkcs12;
    }

    public function setPkcs12(?string $pkcs12)
    {
        $this->pkcs12 = $pkcs12;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password)
    {
        $this->password = $password;
    }

    public function getCertificateObject(): ?Certificate
    {
        return $this->certificateObject;
    }

    public function setCertificateObject(?Certificate $certificateObject)
    {
        $this->certificateObject = $certificateObject;
    }
}
