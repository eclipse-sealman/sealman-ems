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

namespace App\Deny;

interface CertificateDenyInterface
{
    public const DELETE_CERTIFICATE = 'deleteCertificate';
    public const GENERATE_CERTIFICATE = 'generateCertificate';
    public const REVOKE_CERTIFICATE = 'revokeCertificate';
    public const DOWNLOAD_CERTIFICATE = 'downloadCertificate';
    public const DOWNLOAD_CA_CERTIFICATE = 'downloadCaCertificate';
    public const DOWNLOAD_PRIVATE_KEY = 'downloadPrivateKey';
    public const DOWNLOAD_PKCS12 = 'downloadPkcs12';
    public const UPLOAD_CERTIFICATES = 'uploadCertificates';
    public const UPLOAD_PKCS12 = 'uploadPkcs12';
}
