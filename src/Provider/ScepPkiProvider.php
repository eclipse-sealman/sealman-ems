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

namespace App\Provider;

use App\Enum\PkiHashAlgorithm;
use App\Enum\PkiKeyType;
use App\Exception\ProviderException;
use App\Provider\Interface\PkiProviderInterface;
use App\Service\OpenSslManager;
use App\Trait\LogsCollectorTrait;
use Carve\ApiBundle\Helper\Arr;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ScepPkiProvider implements PkiProviderInterface
{
    use LogsCollectorTrait;

    protected ProviderHttpClient $httpClient;

    /**
     * @param string $projectDir                 directory for provider to find needed commands (/bin/scep)
     * @param string $certificateRequestSubDir   directory for provider to create temporary files (provider should remove created folders and files)
     * @param string $scepUrl                    SCEP URL
     * @param string $crlUrl                     SCEP CRL URL
     * @param string $revocationUrl              SCEP Revocation URL
     * @param int    $scepTimeout                SCEP request timeout
     * @param bool   $verifyServerSslCertificate Should verify server SSL certificate?
     * @param string $user                       Basic Auth user
     * @param string $password                   Basic Auth password
     */
    public function __construct(
        protected string $projectDir,
        protected string $certificateRequestSubDir,
        protected OpenSslManager $openSslManager,
        protected string $scepUrl,
        protected string $crlUrl,
        protected string $revocationUrl,
        HttpClientInterface $httpClient,
        int $scepTimeout,
        bool $verifyServerSslCertificate,
        ?string $user = null,
        ?string $password = null,
    ) {
        $this->configureHttpClient($httpClient, $scepTimeout, $verifyServerSslCertificate, $user, $password);
    }

    protected function configureHttpClient(HttpClientInterface $httpClient, int $scepTimeout, bool $verifyServerSslCertificate, ?string $user = null, ?string $password = null)
    {
        $options = [
            'verify_peer' => $verifyServerSslCertificate,
            'verify_host' => $verifyServerSslCertificate,
            'max_duration' => $scepTimeout,
            'timeout' => $scepTimeout,
        ];

        if (null !== $user && null !== $password) {
            $options['auth_basic'] = $user.':'.$password;
        }

        // withOptions() returns a new instance of the client with new default options
        $httpClient = $httpClient->withOptions($options);

        $this->httpClient = new ProviderHttpClient($this, $httpClient, 'SCEP Server');
    }

    public function getCaCertificate(): string
    {
        $data = [
            'message' => 1,
            'operation' => 'GetCACert',
        ];

        // GET request with basic auth
        $httpCaResponse = $this->httpClient->get($this->scepUrl, $data);

        if ('' === $httpCaResponse) {
            throw new ProviderException($this->addLogError('log.scepPkiProvider.getCaCertificate.invalidResponse'));
        }

        // Method extracts CA certificate from SCEP GetCACert HTTP response (can be X509 or #PKCS7 - with intermediate CAs)
        $caCertificate = $this->extractCaCertificateFromHttpCaResponse($httpCaResponse);

        if (null === $caCertificate) {
            throw new ProviderException($this->addLogCritical('log.scepPkiProvider.getCaCertificate.caUnavailable'));
        }

        $caCertificateData = openssl_x509_parse($caCertificate);
        if (false === $caCertificateData || !isset($caCertificateData['subject']) || !is_array($caCertificateData['subject'])) {
            throw new ProviderException($this->addLogCritical('log.scepPkiProvider.getCaCertificate.caInvalid'));
        }

        return $caCertificate;
    }

    /**
     * Generates SCEP request message with CSR to be signed by SCEP server.
     *
     * The method creates a self-signed certificate matching the provided private key,
     * which is used for encryption of the SCEP response message.
     *
     * @param PkiHashAlgorithm $hashAlgorithm    Hash algorithm for CSR signing
     * @param string           $selfSignPrivate  Private key (PEM format) for self-signing
     * @param string           $caCertificatePem CA certificate in PEM format
     * @param string           $csr              Certificate Signing Request in PEM format
     *
     * @return string SCEP request message
     *
     * @throws ProviderException      When CSR generation or SCEP request creation fails
     * @throws ProcessFailedException When openssl or scep command fails
     */
    protected function generateScepRequestMessage(PkiHashAlgorithm $hashAlgorithm, string $selfSignPrivate, string $caCertificatePem, string $csr): string
    {
        /**
         * Steps to generate SCEP request message:
         *  1. Create self signed certificate matching provided private key to be used for encryption of SCEP response message
         *  2. Create SCEP request message with CSR which should be signed by SCEP server.
         */

        // 1. Create self signed certificate matching provided private key to be used for encryption of SCEP response message
        $csrDataArray = openssl_csr_get_subject($csr);

        $certificateSubject = Arr::get($csrDataArray, 'CN', 'default');

        $csrFullSubjectString = $this->openSslManager->getFullSubjectStringWithUpdatedCommonName(
            certificateData: $csrDataArray,
            commonName: 'selfSigned_'.$certificateSubject,
            subjectPrefix: ''
        );

        $selfSignCsr = $this->openSslManager->generateCsr(PkiKeyType::RSA4096, $hashAlgorithm, $selfSignPrivate, $csrFullSubjectString);

        $selfSignPublic = $this->openSslManager->selfSignCsr(
            keyType: PkiKeyType::RSA4096,
            hashAlgorithm: $hashAlgorithm,
            privateKey: $selfSignPrivate,
            csr: $selfSignCsr,
            days: 365
        );

        // 2. Create SCEP request message with CSR which should be signed by SCEP server.
        $scepRequest = $this->openSslManager->generateScepRequestMessage(
            selfSignedPublic: $selfSignPublic,
            selfSignedPrivate: $selfSignPrivate,
            caPublic: $caCertificatePem,
            csr: $csr
        );

        if (!$scepRequest) {
            throw new ProviderException($this->addLogError('log.scepPkiProvider.signCsr.certificateRequestFailed', ['url' => $this->scepUrl, 'exceptionMessage' => $output ? $output : 'N/A']));
        }

        return $scepRequest;
    }

    /**
     * Method signs CSR $csr via SCEP using $caCertificatePem and returns signed certificate
     * $keyType is left for future use (for other PKI protocols and when SCEP will support other keytypes)
     * SCEP request generation command does not support other key types than RSA.
     */
    public function signCsr(PkiHashAlgorithm $hashAlgorithm, PkiKeyType $keyType, string $caCertificatePem, string $csr): string
    {
        /**
         * Steps to sign CSR via SCEP:
         *  1. Create private key to be self signed to encrypt SCEP response message
         *      (self signed certificate will be used to encrypt, private key to decrypt SCEP response message)
         *  2. Create SCEP request message with CSR which should be signed by SCEP server
         *      (CA certificate will be used to encrypt enveloped CSR, self signed key and certificate will be used for signing SCEP request message, self signed public key will be generated inside generateScepRequestMessage method)
         * 3. Send SCEP request message to SCEP server and get response
         * 4. Validate received parameters in SCEP response (message type, PKI status, fail info)
         *      (Exceptions will be thrown when validation fails with logs)
         * 5. Verify and extract SCEP response message using CA certificate
         *      (SCEP server has signed response message with CA private key, so we can verify it with CA public key and then extract response message)
         * 6. Decrypt SCEP envelope with self signed private key
         *      (SCEP server has encrypted certificate with self signed public key, so we can decrypt it with self signed private key)
         * 7. Extract certificate from decrypted SCEP envelope - this is signed certificate for provided CSR signed by CA private key on SCEP server.
         */

        // 1. Create private key to be self signed to encrypt SCEP response message
        // Creation of privateKey to self sign SCEP envelope
        // $selfSignPrivate = openssl_pkey_new($defaultConfigArgs);
        $selfSignPrivate = $this->openSslManager->generatePrivateKey(PkiKeyType::RSA4096);

        // 2. Create SCEP request message with CSR which should be signed by SCEP server
        $scepRequest = $this->generateScepRequestMessage($hashAlgorithm, $selfSignPrivate, $caCertificatePem, $csr);

        // 3. Send SCEP request message to SCEP server and get response
        $data = [
                'operation' => 'PKIOperation',
                'message' => $scepRequest,
            ];

        try {
            // GET request with basic auth
            $scepResponse = $this->httpClient->get($this->scepUrl, $data);
        } catch (ProviderException $exception) {
            $exception->addLogModel($this->addLogError('log.scepPkiProvider.signCsr.certificateResponseFailed', ['url' => $this->scepUrl]));
            throw $exception;
        }

        // 4. Validate received parameters in SCEP response (message type, PKI status, fail info)
        $messageType = $this->openSslManager->getScepMessageType($scepResponse);
        // 3 - SCEP respose
        if ('3' !== $messageType) {
            throw new ProviderException($this->addLogError('log.scepPkiProvider.signCsr.invalidMessageType', ['messageType' => $messageType ?? 'N/A']));
        }
        $pkiStatus = $this->openSslManager->getScepPkiStatus($scepResponse);
        // 0 - SUCCESS
        if ('0' !== $pkiStatus) {
            $failInfo = $this->openSslManager->getScepFailInfo($scepResponse);
            $failInfoText = $this->openSslManager->getScepFailInfoText($scepResponse);
            throw new ProviderException($this->addLogError('log.scepPkiProvider.signCsr.pkiStatusError', ['pkiStatus' => $pkiStatus ?? 'N/A', 'failInfo' => $failInfo ?? 'N/A', 'failInfoText' => $failInfoText ?? 'N/A']));
        }

        // 5. Verify and extract SCEP response message using CA certificate
        $scepResponseMessage = $this->openSslManager->extractVerifiedScepResponseMessage(
                scepResponse: $scepResponse,
                caPublic: $caCertificatePem,
            );

        // Arbitrary length check to make sure that response message is not empty or too short (can be error message)
        if (strlen($scepResponseMessage) < 10) {
            throw new ProviderException($this->addLogError('log.scepPkiProvider.signCsr.verificationFailed'));
        }

        // 6. Decrypt SCEP envelope with self signed private key
        $scepEnvelope = $this->openSslManager->extractScepEnvelope(
                scepResponseMessage: $scepResponseMessage,
                selfSignPrivate: $selfSignPrivate,
            );

        // Arbitrary length check to make sure that response message is not empty or too short (can be error message)F
        if (strlen($scepEnvelope) < 10) {
            throw new ProviderException($this->addLogError('log.scepPkiProvider.signCsr.decryptionFailed'));
        }

        // 7. Extract certificate from decrypted SCEP envelope - this is signed certificate for provided CSR signed by CA private key on SCEP server.
        $certificatePem = $this->openSslManager->extractCertificatesFromScepEnvelope($scepEnvelope, 'DER');
        if (!$certificatePem) {
            throw new ProviderException($this->addLogError('log.scepPkiProvider.signCsr.decodingCertificateFailed'));
        }

        return $certificatePem;
    }

    /**
     * Revokes a certificate by serial number via SCEP revocation endpoint.
     *
     * Sends revocation request with reason "superseded" to the SCEP revocation service.
     *
     * @param string $serialNumber Serial number of the certificate to revoke
     *
     * @throws ProviderException When revocation request fails or returns invalid response
     */
    public function revokeCertificate(string $serialNumber): void
    {
        $data = ['serialNumber' => $serialNumber, 'reason' => 'superseded'];

        // POST request with basic auth
        $result = $this->httpClient->post($this->revocationUrl, $data, toArray: true);

        $responseStatus = Arr::get($result, 'status');
        if (null === $responseStatus) {
            throw new ProviderException($this->addLogError('log.scepPkiProvider.revokeCertificate.invalidResponse'));
        }

        if ('SUCCESS' !== $responseStatus) {
            $errorInfo = Arr::get($result, 'info', 'N/A');
            throw new ProviderException($this->addLogError('log.scepPkiProvider.revokeCertificate.certificateRevocationFailed', ['url' => $this->revocationUrl, 'exceptionMessage' => $errorInfo]));
        }
    }

    public function getCrl(): string
    {
        $crl = $this->httpClient->get($this->crlUrl, options: ['auth_basic' => null]);

        if (!self::isCrlValid($crl)) {
            throw new ProviderException($this->addLogError('log.scepPkiProvider.getCrl.invalidResponse'));
        }

        return $crl;
    }

    /**
     * Get CRL by URL without the need of full configuration.
     */
    public static function getCrlByUrl(HttpClientInterface $httpClient, string $scepUrl, int $scepTimeout, bool $verifyServerSslCertificate): ?string
    {
        try {
            // Execute request with raw $httpClient
            $httpClient = $httpClient->withOptions([
                'verify_peer' => $verifyServerSslCertificate,
                'verify_host' => $verifyServerSslCertificate,
                'max_duration' => $scepTimeout,
                'timeout' => $scepTimeout,
            ]);
            $response = $httpClient->request('GET', $scepUrl);
            $crl = $response->getContent();
        } catch (\Throwable $exception) {
            return null;
        }

        if (!self::isCrlValid($crl)) {
            return null;
        }

        return $crl;
    }

    protected static function isCrlValid(string $crl): bool
    {
        if (false === strpos($crl, '-----BEGIN X509 CRL-----')) {
            return false;
        }

        return true;
    }

    /**
     * Extracts CA certificate from SCEP GetCACert HTTP response.
     *
     * The response can contain either an X509 certificate (single root CA) or a PKCS7 envelope
     * (root CA with intermediate certificates). The method attempts to parse as X509 first,
     * then falls back to PKCS7 parsing if unsuccessful.
     *
     * @param string $httpCaResponse Raw HTTP response data from SCEP GetCACert operation
     *
     * @return string|null PEM-encoded certificate chain, or null if parsing fails
     *
     * @throws ProviderException When certificate is invalid or cannot be parsed
     */
    protected function extractCaCertificateFromHttpCaResponse(string $httpCaResponse): ?string
    {
        $encodedCaCertificate = base64_encode($httpCaResponse);

        if (false === strpos($encodedCaCertificate, "\n")) {
            // if $content is not formatted - some SCEP clients sends data that way
            $encodedCaCertificate = chunk_split($encodedCaCertificate, 64);
        }

        // $encodedCaCertificate can be X509 (root CA) or #PKCS7 (root CA with intermediate CAs)
        // First try to parse it as X509
        $caCertificate = $this->parseX509Certificate($encodedCaCertificate);
        // When unsuccessfull try to parse it as #PKCS7
        if (null === $caCertificate) {
            $caCertificate = $this->parsePCKS7Certificate($encodedCaCertificate);
        }

        return $caCertificate;
    }

    /**
     * Parses X509 certificate from base64-encoded format.
     *
     * Validates that the parsed certificate contains required subject information.
     *
     * @param string $encodedCertificate Base64-encoded certificate data (without BEGIN/END markers)
     *
     * @return string|null PEM-encoded certificate with BEGIN/END markers, or null if parsing fails
     */
    protected function parseX509Certificate(string $encodedCertificate): ?string
    {
        $certificate = "-----BEGIN CERTIFICATE-----\n".$encodedCertificate.'-----END CERTIFICATE-----';

        $parsedCertificate = openssl_x509_parse($certificate);
        if (false === $parsedCertificate) {
            return null;
        }

        if (!isset($parsedCertificate['subject']) || !is_array($parsedCertificate['subject'])) {
            return null;
        }

        return $certificate;
    }

    /**
     * Parses PKCS7 envelope containing certificate chain from base64-encoded format.
     *
     * Extracts all certificates from PKCS7 envelope and returns them as individual
     * PEM-encoded certificates concatenated together.
     *
     * @param string $encodedCertificate Base64-encoded PKCS7 certificate envelope (without BEGIN/END markers)
     *
     * @return string|null Concatenated PEM-encoded certificates with BEGIN/END markers, or null if parsing fails
     *
     * @throws ProviderException When PKCS7 envelope structure is invalid
     */
    protected function parsePCKS7Certificate(string $encodedCertificate): ?string
    {
        $pkcs7 = "-----BEGIN PKCS7-----\n".$encodedCertificate.'-----END PKCS7-----';

        $output = $this->openSslManager->extractCertificatesFromScepEnvelope($pkcs7, 'PEM');
        if (!$output) {
            return null;
        }

        $certificates = [];
        $certificateLines = [];
        $read = false;

        // Output includes multiple certificates with subject and issuer data before each of them
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if ($read) {
                $certificateLines[] = $line;
            }

            if (false !== strpos($line, '-----BEGIN CERTIFICATE-----')) {
                $read = true;
                $certificateLines[] = $line;
            }

            if (false !== strpos($line, '-----END CERTIFICATE-----')) {
                $read = false;
                $certificateLines[] = "\n";
                $certificates[] = implode("\n", $certificateLines);
                $certificateLines = [];
            }
        }

        if (0 === count($certificates)) {
            return null;
        }

        return implode('', $certificates);
    }
}
