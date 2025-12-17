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

namespace Tests\Utilities\Mock\PkiProvider;

use App\Enum\PkiHashAlgorithm;
use App\Enum\PkiKeyLength;
use App\Provider\Interface\PkiProviderInterface;
use App\Trait\LogsCollectorTrait;

class MockPkiProvider implements PkiProviderInterface
{
    use LogsCollectorTrait;

    public function __construct(
        private string $caCertificate,
        private string $caPrivateKey,
    ) {
    }

    public function getCaCertificate(): string
    {
        return $this->caCertificate;
    }

    public function signCsr(PkiHashAlgorithm $hashAlgorithm, PkiKeyLength $keyLength, string $caCertificatePem, \OpenSSLCertificateSigningRequest $csr): string
    {
        $certificate = \openssl_csr_sign(
            $csr,
            $caCertificatePem,
            $this->caPrivateKey,
            365,
            [
                'digest_alg' => $hashAlgorithm->value,
                'private_key_bits' => $keyLength->value,
            ],
            \random_int(1, \intval((PHP_INT_MAX / 2) - 1)) // Serial number
        );

        openssl_x509_export($certificate, $exportedCertificate);

        return $exportedCertificate;
    }

    public function revokeCertificate(string $serialNumber): void
    {
        // Nothing to do. Assume that certificate is revoked successfully.
    }

    public function getCrl(): string
    {
        // Implement when needed
        return 'CRL content';
    }
}
