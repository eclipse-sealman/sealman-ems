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

class CertificateUploadFilesModel
{
    /**
     * Certificate's private key - TUS uploaded file.
     */
    private ?string $privateKey = null;

    /**
     * SSL certificate - TUS uploaded file.
     */
    private ?string $certificate = null;

    /**
     * Certificate's CA certificate - TUS uploaded file.
     */
    private ?string $certificateCa = null;

    /**
     * Certificate object - setup by controller.
     */
    private ?Certificate $certificateObject = null;

    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    public function setPrivateKey(?string $privateKey)
    {
        $this->privateKey = $privateKey;
    }

    public function getCertificate(): ?string
    {
        return $this->certificate;
    }

    public function setCertificate(?string $certificate)
    {
        $this->certificate = $certificate;
    }

    public function getCertificateCa(): ?string
    {
        return $this->certificateCa;
    }

    public function setCertificateCa(?string $certificateCa)
    {
        $this->certificateCa = $certificateCa;
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
