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

namespace Tests\Suites\Unit\PkiScepEncoding;

use App\Enum\PkiHashAlgorithm;
use App\Enum\PkiKeyType;
use App\Provider\ScepPkiProvider;
use App\Service\OpenSslManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\Utility\Accessor;

#[Group('full')]
#[Group('smoke')]
#[Group('Unit')] // To be used in CI parallel tests
// This class uses OpenSslManager to execute required operations
class PkiScepEncodingTest extends AbstractTestCase
{
    public static function pkiProvider(): array
    {
        $resultArray = [];
        foreach (PkiKeyType::cases() as $caPkiType) {
            if (PkiKeyType::ED25519 === $caPkiType || PkiKeyType::ED448 === $caPkiType) {
                // Skip EDDSA for CA as not supported by openssl for CMS encryption - CA cannot be this type for now
                continue;
            }
            foreach (PkiKeyType::cases() as $pkiType) {
                foreach (PkiHashAlgorithm::cases() as $caHashAlgorithm) {
                    foreach (PkiHashAlgorithm::cases() as $hashAlgorithm) {
                        $testCaseName = sprintf(
                            '%s_CA_%s_with_%s_CA_%s',
                            $caPkiType->value,
                            $caHashAlgorithm->value,
                            $pkiType->value,
                            $hashAlgorithm->value
                        );
                        $resultArray[$testCaseName] = [
                           'caPkiType' => $caPkiType,
                           'pkiType' => $pkiType,
                           'caHashAlgorithm' => $caHashAlgorithm,
                           'hashAlgorithm' => $hashAlgorithm,
                        ];
                    }
                }
            }
        }

        return $resultArray;
    }

    /**
     * Tests SCEP request message generation by checking correctness of generated and then decoded CSR content.
     */
    #[DataProvider('pkiProvider')]
    public function testScepRequestMessage(PkiKeyType $caPkiType, PkiKeyType $pkiType, PkiHashAlgorithm $caHashAlgorithm, PkiHashAlgorithm $hashAlgorithm): void
    {
        /*
        Test process:
        1) Service preparations
        2) CA config content preparation - with required extensions for CA certificate
        ROOT CA PROCESS:
        3) ROOT CA generation - create private key, CSR and self-signed certificate
        4) Private key and CSR generation for SCEP request - it will be used as content for SCEP request message and will be signed and encrypted in the process of SCEP request message generation
        5) SelfSigned key generation for SCEP request message signing
        6) SCEP request message generation based on ROOT CA - using ScepPkiProvider::generateScepRequestMessage() method
        7) Extract enveloped PKCS7 content from SCEP request message - similar process as in SCEP server applications
        8) Decrypt CSR included in SCEP request message - using ROOT CA
        9) Verify decoded CSR content matches original CSR content
        SUB CA PROCESS:
        10) Sub CA generation - create private key, CSR and certificate signed by ROOT CA
        11) Private key and CSR generation for SCEP request - it will be used as content for SCEP request message and will be signed and encrypted in the process of SCEP request message generation
        12) SelfSigned key generation for SCEP request message signing
        13) SCEP request message generation based on Sub CA - using ScepPkiProvider::generateScepRequestMessage() method
        14) Extract enveloped PKCS7 content from SCEP request message - similar process as in SCEP server applications
        15) Decrypt CSR included in SCEP request message - using Sub CA
        16) Verify decoded CSR content matches original CSR content
        */

        // 1) Service preparations
        $projectDir = self::$kernel->getProjectDir();
        $cacheDir = '/var/cache/tests/';

        $openSslManager = $this->getService(OpenSslManager::class);
        $scepPkiProvider = new ScepPkiProvider(
            $projectDir,
            $cacheDir,
            $openSslManager,
            'not-needed-for-this-test',
            'not-needed-for-this-test',
            'not-needed-for-this-test',
            $this->getService(HttpClientInterface::class),
            111,// not important for this test
            false,// not important for this test
            'not-needed-for-this-test',
            'not-needed-for-this-test',
        );

        // 2) CA config content preparation - with required extensions for CA certificate
        $caConfigContent = '
basicConstraints = CA:true
keyUsage = keyCertSign, cRLSign, digitalSignature
subjectKeyIdentifier = hash
authorityKeyIdentifier = keyid:always,issuer:always
        ';

        // 3) ROOT CA generation - create private key, CSR and self-signed certificate
        $caPrivateKey = $openSslManager->generatePrivateKey($caPkiType);

        $caCsr = $openSslManager->generateCsr(
            keyType: $caPkiType,
            hashAlgorithm: $caHashAlgorithm,
            privateKey: $caPrivateKey,
            subject: '/C=DE/CN=TestCA',
        );

        $caPublicKey = $openSslManager->selfSignCsr(
            keyType: $caPkiType,
            hashAlgorithm: $caHashAlgorithm,
            privateKey: $caPrivateKey,
            csr: $caCsr,
            days: 3650,
            configContent: $caConfigContent,
        );

        // 4) Private key and CSR generation for SCEP request - it will be used as content for SCEP request message and will be signed and encrypted in the process of SCEP request message generation
        $privateKey = $openSslManager->generatePrivateKey($pkiType);

        $csr = $openSslManager->generateCsr(
            keyType: $pkiType,
            hashAlgorithm: $hashAlgorithm,
            privateKey: $privateKey,
            subject: '/C=DE/CN=TestDevice',
        );

        // 5) SelfSigned key generation for SCEP request message signing
        $selfSignPrivate = $openSslManager->generatePrivateKey(PkiKeyType::RSA4096);

        // 6) SCEP request message generation based on ROOT CA - using ScepPkiProvider::generateScepRequestMessage() method
        $scepRequest = Accessor::invoke(
            $scepPkiProvider,
            'generateScepRequestMessage',
            [
               PkiHashAlgorithm::SHA512,
               $selfSignPrivate,
               $caPublicKey,
               $csr,
            ]
        );

        $this->assertNotEmpty($scepRequest, 'SCEP request generation failed');

        // 7) Extract enveloped PKCS7 content from SCEP request message - similar process as in SCEP server applications
        $envelopedPkcs7 = $openSslManager->getScepEnvelopedRequestPkcs7Content($scepRequest);
        $this->assertNotEmpty($envelopedPkcs7, 'SCEP Enveloped PKCS7 content extraction failed');

        // 8) Decrypt CSR included in SCEP request message - using ROOT CA
        $scepContent = $openSslManager->decryptPkcs7Envelope($envelopedPkcs7, $caPublicKey, $caPrivateKey);

        if (empty($scepContent)) {
            $this->fail('SCEP PKCS7 decryption failed - empty content');
        }

        // 9) Verify decoded CSR content matches original CSR content
        $csrDer = $openSslManager->convertCsrPemToDer($csr);
        $md5Original = md5($csrDer);
        $md5Decoded = md5($scepContent);
        $this->assertEquals($md5Original, $md5Decoded, 'Decoded CSR content does not match original CSR');

        // 10) Sub CA generation - create private key, CSR and certificate signed by ROOT CA
        $subCaPrivateKey = $openSslManager->generatePrivateKey($caPkiType);

        $subCaCsr = $openSslManager->generateCsr(
            keyType: $caPkiType,
            hashAlgorithm: $caHashAlgorithm,
            privateKey: $subCaPrivateKey,
            subject: '/C=DE/CN=TestSubCA',
        );

        $subCaPublicKey = $openSslManager->signCsr(
            keyType: $caPkiType,
            hashAlgorithm: $caHashAlgorithm,
            caPublic: $caPublicKey,
            caPrivate: $caPrivateKey,
            csr: $subCaCsr,
            days: 3650,
            configContent: $caConfigContent,
        );

        // 11) Private key and CSR generation for SCEP request - it will be used as content for SCEP request message and will be signed and encrypted in the process of SCEP request message generation
        $privateKey = $openSslManager->generatePrivateKey($pkiType);

        $csr = $openSslManager->generateCsr(
            keyType: $pkiType,
            hashAlgorithm: $hashAlgorithm,
            privateKey: $privateKey,
            subject: '/C=DE/CN=TestDevice',
        );

        // 12) SelfSigned key generation for SCEP request message signing
        $selfSignPrivate = $openSslManager->generatePrivateKey(PkiKeyType::RSA4096);

        // 13) SCEP request message generation based on Sub CA - using ScepPkiProvider::generateScepRequestMessage() method
        $scepRequest = Accessor::invoke(
            $scepPkiProvider,
            'generateScepRequestMessage',
            [
               PkiHashAlgorithm::SHA512,
               $selfSignPrivate,
               $caPublicKey,
               $csr,
            ]
        );

        $this->assertNotEmpty($scepRequest, 'SCEP request generation failed');

        // 14) Extract enveloped PKCS7 content from SCEP request message - similar process as in SCEP server applications
        $envelopedPkcs7 = $openSslManager->getScepEnvelopedRequestPkcs7Content($scepRequest);
        $this->assertNotEmpty($envelopedPkcs7, 'SCEP Enveloped PKCS7 content extraction failed');

        // 15) Decrypt CSR included in SCEP request message - using ROOT CA
        $scepContent = $openSslManager->decryptPkcs7Envelope($envelopedPkcs7, $caPublicKey, $caPrivateKey);

        if (empty($scepContent)) {
            $this->fail('SCEP PKCS7 decryption failed - empty content');
        }

        // 16) Verify decoded CSR content matches original CSR content
        $csrDer = $openSslManager->convertCsrPemToDer($csr);
        $md5Original = md5($csrDer);
        $md5Decoded = md5($scepContent);
        $this->assertEquals($md5Original, $md5Decoded, 'Decoded CSR content does not match original CSR');
    }
}
