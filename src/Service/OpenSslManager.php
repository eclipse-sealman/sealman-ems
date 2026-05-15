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

namespace App\Service;

use App\Enum\PkiHashAlgorithm;
use App\Enum\PkiKeyType;
use App\Exception\UnsupportedValueException;
use App\Service\Helper\FileManagerTrait;
use App\Service\Helper\SymfonyDirTrait;
use App\Service\Trait\RunCommandHelperTrait;
use Carve\ApiBundle\Helper\Arr;
use phpseclib3\File\ASN1;
use Symfony\Component\Process\Exception\ProcessFailedException;

class OpenSslManager
{
    use FileManagerTrait;
    use RunCommandHelperTrait;
    use SymfonyDirTrait;

    // OIDs for SCEP message parsing
    public const OID_SCEP_MESSAGE_TYPE = '2.16.840.1.113733.1.9.2';
    public const OID_SCEP_PKI_STATUS = '2.16.840.1.113733.1.9.3';
    public const OID_SCEP_FAIL_INFO = '2.16.840.1.113733.1.9.4';
    public const OID_SCEP_FAIL_INFO_TEXT = '1.3.6.1.5.5.7.24.1';

    // used for testing
    public const OID_SCEP_SENDER_NONCE = '2.16.840.1.113733.1.9.5';
    public const OID_SCEP_TRANSACTION_ID = '2.16.840.1.113733.1.9.7';
    public const OID_SCEP_ENVELOPED_PKCS7 = '2.16.840.1.113733.1.9.6';
    public const OID_SCEP_ENVELOPED_REQUEST_PKCS7 = '1.2.840.113549.1.7.1';

    /**
     * Generates a new private key with specified algorithm and parameters.
     *
     * Creates a cryptographic private key file using OpenSSL based on the specified key type.
     * Supports RSA (2048, 4096 bits), EC (P-521), and EdDSA (ED25519, ED448) algorithms.
     *
     * @param PkiKeyType $keyType Type of key to generate (RSA2048, RSA4096, EC_P_521, ED448, ED25519)
     *
     * @return string Private key in PEM format (includes '-----BEGIN PRIVATE KEY-----' wrapper)
     *
     * @throws ProcessFailedException    When key generation command fails
     * @throws UnsupportedValueException When key type is not supported
     */
    public function generatePrivateKey(PkiKeyType $keyType): string
    {
        $privateKeyPath = $this->fileManager->getTmpFile();

        try {
            $command = array_merge(
                [
                    'openssl',
                    'genpkey',
                    '-out',
                    $privateKeyPath,
                ],
                $this->getPrivateKeyAlgorithmCommandParameters($keyType),
            );

            $this->runCommand($command);

            return file_get_contents($privateKeyPath);
        } finally {
            $this->fileManager->removeFile($privateKeyPath);
        }
    }

    /**
     * Generates a Certificate Signing Request (CSR) from a private key.
     *
     * Creates a CSR that can be signed by a Certificate Authority to obtain an X509 certificate.
     * Supports optional OpenSSL configuration for custom CSR attributes and extensions.
     *
     * @param PkiKeyType       $keyType       Type of key used for CSR (algorithm must match private key)
     * @param PkiHashAlgorithm $hashAlgorithm Hash algorithm for signing the CSR (SHA256, SHA384, SHA512)
     * @param string           $privateKey    Private key in PEM format
     * @param string           $subject       X500 distinguished name (e.g., '/C=US/ST=CA/L=SF/O=Company/CN=example.com')
     * @param array            $addText       Optional X509 extensions as key-value pairs (e.g., ['subjectAltName' => 'DNS:example.com'])
     * @param string|null      $configContent Optional OpenSSL configuration content for custom CSR settings
     *
     * @return string CSR in PEM format (includes '-----BEGIN CERTIFICATE REQUEST-----' wrapper)
     *
     * @throws ProcessFailedException    When CSR generation command fails
     * @throws UnsupportedValueException When key type or hash algorithm is not supported
     */
    public function generateCsr(PkiKeyType $keyType, PkiHashAlgorithm $hashAlgorithm, string $privateKey, string $subject, array $addText = [], ?string $configContent = null): string
    {
        if (null !== $configContent) {
            $configPath = $this->fileManager->dumpTmpFile($configContent);
            $configPathParameter = ['-config', $configPath];
        }

        $privateKeyPath = $this->fileManager->dumpTmpFile($privateKey);
        $csrPath = $this->fileManager->getTmpFile();

        try {
            $command = array_merge(
                [
                    'openssl',
                    'req',
                    '-new',
                    '-key',
                    $privateKeyPath,
                    '-out',
                    $csrPath,
                ],
                $configPathParameter ?? [],
                $this->getAlgorithmCommandParameters($keyType, $hashAlgorithm),
                $this->getSubjectCommandParameter($subject),
                $this->getAddTextCommandParameter($addText),
            );

            $this->runCommand($command);

            return file_get_contents($csrPath);
        } finally {
            $this->fileManager->removeFile($privateKeyPath);
            $this->fileManager->removeFile($csrPath);
            if (isset($configPath)) {
                $this->fileManager->removeFile($configPath);
            }
        }
    }

    /**
     * Creates a self-signed X509 certificate from a CSR.
     *
     * Generates a self-signed certificate by signing the CSR with its own private key.
     * Useful for testing, development, or creating root/intermediate CA certificates.
     * The certificate is valid for the specified number of days from creation.
     *
     * @param PkiKeyType       $keyType       Type of key used for signing (algorithm must match CSR and private key)
     * @param PkiHashAlgorithm $hashAlgorithm Hash algorithm for signing (SHA256, SHA384, SHA512)
     * @param string           $privateKey    Private key in PEM format
     * @param string           $csr           Certificate Signing Request in PEM format
     * @param int              $days          Certificate validity period in days (default: 365)
     * @param array            $addText       Optional X509 extensions as key-value pairs
     * @param string|null      $configContent Optional OpenSSL configuration for custom certificate extensions
     *
     * @return string Self-signed X509 certificate in PEM format (includes '-----BEGIN CERTIFICATE-----' wrapper)
     *
     * @throws ProcessFailedException    When certificate generation command fails
     * @throws UnsupportedValueException When key type or hash algorithm is not supported
     */
    public function selfSignCsr(PkiKeyType $keyType, PkiHashAlgorithm $hashAlgorithm, string $privateKey, string $csr, int $days = 365, array $addText = [], ?string $configContent = null): string
    {
        if (null !== $configContent) {
            $configPath = $this->fileManager->dumpTmpFile($configContent);
            $configPathParameter = ['-extfile', $configPath];
        }

        $privateKeyPath = $this->fileManager->dumpTmpFile($privateKey);
        $csrPath = $this->fileManager->dumpTmpFile($csr);
        $signedCsrPath = $this->fileManager->getTmpFile();

        // openssl x509 -signkey domain.key -in domain.csr -req -days 365 -out domain.crt
        try {
            $command = array_merge(
                [
                    'openssl',
                    'x509',
                    '-signkey',
                    $privateKeyPath,
                    '-in',
                    $csrPath,
                    '-req',
                    '-days',
                    (string) $days,
                    '-out',
                    $signedCsrPath,
                ],
                $configPathParameter ?? [],
                $this->getAlgorithmCommandParameters($keyType, $hashAlgorithm),
                $this->getAddTextCommandParameter($addText),
            );

            $this->runCommand($command);

            return file_get_contents($signedCsrPath);
        } finally {
            $this->fileManager->removeFile($privateKeyPath);
            $this->fileManager->removeFile($csrPath);
            $this->fileManager->removeFile($signedCsrPath);
            if (isset($configPath)) {
                $this->fileManager->removeFile($configPath);
            }
        }
    }

    /**
     * Signs a CSR using a CA's private key to create a certificate.
     *
     * Issues an X509 certificate by signing a Certificate Signing Request with a Certificate Authority's
     * private key. The resulting certificate is signed by the CA and trusted wherever the CA certificate is trusted.
     * Validates that the CSR and CA key/certificate use compatible algorithms.
     *
     * @param PkiKeyType       $keyType       Type of key used for signing (algorithm must match CSR and CA private key)
     * @param PkiHashAlgorithm $hashAlgorithm Hash algorithm for signing (SHA256, SHA384, SHA512)
     * @param string           $caPublic      CA certificate in PEM format (used for linking in issued certificate)
     * @param string           $caPrivate     CA private key in PEM format (used to sign the CSR)
     * @param string           $csr           Certificate Signing Request in PEM format to be signed
     * @param int              $days          Certificate validity period in days (default: 365)
     * @param array            $addText       Optional X509 extensions as key-value pairs (e.g., ['keyUsage' => 'critical,digitalSignature'])
     * @param string|null      $configContent Optional OpenSSL configuration for custom certificate extensions
     *
     * @return string Signed X509 certificate in PEM format (includes '-----BEGIN CERTIFICATE-----' wrapper)
     *
     * @throws ProcessFailedException    When certificate signing command fails or CA key is invalid
     * @throws UnsupportedValueException When key type or hash algorithm is not supported
     */
    public function signCsr(PkiKeyType $keyType, PkiHashAlgorithm $hashAlgorithm, string $caPublic, string $caPrivate, string $csr, int $days = 365, array $addText = [], ?string $configContent = null): string
    {
        if (null !== $configContent) {
            $configPath = $this->fileManager->dumpTmpFile($configContent);
            $configPathParameter = ['-extfile', $configPath];
        }

        $caPublicPath = $this->fileManager->dumpTmpFile($caPublic);
        $caPrivatePath = $this->fileManager->dumpTmpFile($caPrivate);
        $csrPath = $this->fileManager->dumpTmpFile($csr);
        $signedCsrPath = $this->fileManager->getTmpFile();

        // openssl x509 -signkey domain.key -in domain.csr -req -days 365 -out domain.crt
        try {
            $command = array_merge(
                [
                    'openssl',
                    'x509',
                    '-req',
                    '-in',
                    $csrPath,
                    '-CAkey',
                    $caPrivatePath,
                    '-CA',
                    $caPublicPath,
                    '-days',
                    (string) $days,
                    '-out',
                    $signedCsrPath,
                ],
                $configPathParameter ?? [],
                $this->getAlgorithmCommandParameters($keyType, $hashAlgorithm),
                $this->getAddTextCommandParameter($addText),
            );

            $this->runCommand($command);

            return file_get_contents($signedCsrPath);
        } finally {
            $this->fileManager->removeFile($csrPath);
            $this->fileManager->removeFile($signedCsrPath);
            $this->fileManager->removeFile($caPublicPath);
            $this->fileManager->removeFile($caPrivatePath);
            if (isset($configPath)) {
                $this->fileManager->removeFile($configPath);
            }
        }
    }

    /**
     * Converts a Certificate Signing Request from PEM to DER format.
     *
     * Transforms CSR from ASCII-armored PEM format to binary DER format.
     * Required for protocols that expect binary input (e.g., SCEP, PKIX).
     *
     * @param string $csr Certificate Signing Request in PEM format
     *
     * @return string CSR in DER (Distinguished Encoding Rules) binary format
     *
     * @throws ProcessFailedException When conversion command fails or CSR format is invalid
     */
    public function convertCsrPemToDer(string $csr): string
    {
        $csrPath = $this->fileManager->dumpTmpFile($csr);
        $csrDerPath = $this->fileManager->getTmpFile();

        // openssl req -in $csrPath -outform DER -out $csrDerPath
        try {
            $command = array_merge(
                [
                    'openssl',
                    'req',
                    '-in',
                    $csrPath,
                    '-outform',
                    'DER',
                    '-out',
                    $csrDerPath,
                ],
            );

            $this->runCommand($command);

            return file_get_contents($csrDerPath);
        } finally {
            $this->fileManager->removeFile($csrPath);
            $this->fileManager->removeFile($csrDerPath);
        }
    }

    /**
     * Builds OpenSSL command parameters for subject DN specification.
     *
     * @param string $subject Full subject Distinguished Name (e.g., '/C=US/ST=State/CN=example.com')
     *
     * @return array Command parameters array with -subj flag and subject value
     */
    protected function getSubjectCommandParameter(string $subject): array
    {
        return [
            '-subj',
            $subject,
        ];
    }

    /**
     * Builds OpenSSL command parameters for X509 extensions.
     *
     * @param array $additionalText Key-value pairs of extensions (e.g., ['basicConstraints' => 'critical,CA:TRUE'])
     *
     * @return array Command parameters array with -addext flag and extension specifications
     */
    protected function getAddTextCommandParameter(array $additionalText = []): array
    {
        $parameters = [];

        foreach ($additionalText as $key => $value) {
            $parameters[] = '-addext';
            $parameters[] = $key.':'.$value;
        }

        return $parameters;
    }

    /**
     * Builds OpenSSL command parameters for hash algorithm.
     *
     * EdDSA key types (ED25519, ED448) don't require explicit hash algorithm specification.
     *
     * @param PkiKeyType       $keyType       Type of key being used
     * @param PkiHashAlgorithm $hashAlgorithm Hash algorithm to use (SHA256, SHA384, SHA512)
     *
     * @return array Command parameters array with hash algorithm flags, or empty array for EdDSA keys
     *
     * @throws UnsupportedValueException When hash algorithm is not supported
     */
    protected function getAlgorithmCommandParameters(PkiKeyType $keyType, PkiHashAlgorithm $hashAlgorithm): array
    {
        $hashAlgorithmOmmittedForKeyTypes = [
            PkiKeyType::ED25519,
            PkiKeyType::ED448,
        ];
        if (in_array($keyType, $hashAlgorithmOmmittedForKeyTypes, true)) {
            return [];
        }

        $parameters = [];
        switch ($hashAlgorithm) {
            case PkiHashAlgorithm::SHA256:
                $parameters[] = '-sha256';
                break;
            case PkiHashAlgorithm::SHA384:
                $parameters[] = '-sha384';
                break;
            case PkiHashAlgorithm::SHA512:
                $parameters[] = '-sha512';
                break;
            default:
                throw new UnsupportedValueException($hashAlgorithm);
        }

        return $parameters;
    }

    /**
     * Builds OpenSSL command parameters for private key generation.
     *
     * Configures algorithm type and key-specific parameters (bit length for RSA, curve for EC, etc.).
     *
     * @param PkiKeyType $keyType Type of key to generate (RSA2048, RSA4096, EC_P_521, ED448, ED25519)
     *
     * @return array Command parameters array with algorithm and key generation options
     *
     * @throws UnsupportedValueException When key type is not supported
     */
    protected function getPrivateKeyAlgorithmCommandParameters(PkiKeyType $keyType): array
    {
        $parameters = [];

        switch ($keyType) {
            case PkiKeyType::RSA2048:
                $parameters[] = '-algorithm';
                $parameters[] = 'RSA';
                $parameters[] = '-pkeyopt';
                $parameters[] = 'rsa_keygen_bits:2048';
                break;
            case PkiKeyType::RSA4096:
                $parameters[] = '-algorithm';
                $parameters[] = 'RSA';
                $parameters[] = '-pkeyopt';
                $parameters[] = 'rsa_keygen_bits:4096';
                break;
            case PkiKeyType::EC_P_521:
                $parameters[] = '-algorithm';
                $parameters[] = 'EC';
                $parameters[] = '-pkeyopt';
                $parameters[] = 'ec_paramgen_curve:P-521';
                $parameters[] = '-pkeyopt';
                $parameters[] = 'ec_param_enc:named_curve';
                break;
            case PkiKeyType::ED448:
                $parameters[] = '-algorithm';
                $parameters[] = 'ED448';
                break;
            case PkiKeyType::ED25519:
                $parameters[] = '-algorithm';
                $parameters[] = 'ED25519';
                break;
            default:
                throw new UnsupportedValueException($keyType);
        }

        return $parameters;
    }

    /**
     * Constructs complete X509 subject Distinguished Name with updated Common Name.
     *
     * Extracts existing subject components (C, ST, L, O) from certificate data and combines
     * them with the new Common Name to create a properly formatted X500 distinguished name.
     *
     * @param array  $certificateData Parsed certificate data array with subject components
     * @param string $commonName      New Common Name to use in the subject
     * @param string $subjectPrefix   Array key prefix for subject components (default: 'subject.')
     *
     * @return string Formatted X500 distinguished name (e.g., '/C=US/ST=CA/L=SF/O=Company/CN=new.example.com')
     */
    public function getFullSubjectStringWithUpdatedCommonName(array $certificateData, string $commonName, string $subjectPrefix = 'subject.'): string
    {
        $subject = '';

        if (Arr::has($certificateData, $subjectPrefix.'C')) {
            $subject .= '/C='.Arr::get($certificateData, $subjectPrefix.'C');
        }

        if (Arr::has($certificateData, $subjectPrefix.'ST')) {
            $subject .= '/ST='.Arr::get($certificateData, $subjectPrefix.'ST');
        }

        if (Arr::has($certificateData, $subjectPrefix.'L')) {
            $subject .= '/L='.Arr::get($certificateData, $subjectPrefix.'L');
        }

        if (Arr::has($certificateData, $subjectPrefix.'O')) {
            $subject .= '/O='.Arr::get($certificateData, $subjectPrefix.'O');
        }

        $subject .= '/CN='.$commonName;

        return $subject;
    }

    /**
     * Generates SCEP request message to be sent to SCEP server.
     *
     * Creates a PKCS7-signed and encrypted SCEP request containing the Certificate Signing Request
     * that will be signed by the SCEP server. The message is signed with the self-signed key and
     * encrypted with the CA certificate.
     *
     * @param string $selfSignedPublic  Self-signed certificate in PEM format (for encryption)
     * @param string $selfSignedPrivate Private key matching self-signed certificate in PEM format
     * @param string $caPublic          CA public certificate in PEM format (for encryption)
     * @param string $csr               Certificate Signing Request in PEM format
     *
     * @return string SCEP request message in binary format ready to send to server
     *
     * @throws ProcessFailedException When SCEP request generation command fails
     */
    public function generateScepRequestMessage(string $selfSignedPublic, string $selfSignedPrivate, string $caPublic, string $csr): string
    {
        $selfSignedPublicPath = $this->fileManager->dumpTmpFile($selfSignedPublic);
        $selfSignedPrivatePath = $this->fileManager->dumpTmpFile($selfSignedPrivate);
        $caPublicPath = $this->fileManager->dumpTmpFile($caPublic);
        $csrPath = $this->fileManager->dumpTmpFile($csr);

        $scepRequestPath = $this->fileManager->getTmpFile();

        // ./bin/scep $selfSignedPublicPath $selfSignedPrivatePath $caPublicPath $csrPath $scepRequestPath
        try {
            $command = [
                $this->projectDir.'/bin/scep',
                $selfSignedPublicPath,
                $selfSignedPrivatePath,
                $caPublicPath,
                $csrPath,
                $scepRequestPath,
            ];

            $this->runCommand($command);

            return file_get_contents($scepRequestPath);
        } finally {
            $this->fileManager->removeFile($selfSignedPublicPath);
            $this->fileManager->removeFile($selfSignedPrivatePath);
            $this->fileManager->removeFile($caPublicPath);
            $this->fileManager->removeFile($csrPath);
            $this->fileManager->removeFile($scepRequestPath);
        }
    }

    /**
     * Decrypts PKCS7 envelope using recipient's private key.
     *
     * Extracts and decrypts the content that was encrypted with the recipient's public certificate.
     *
     * @param string $scepPkcs7Envelope Encrypted PKCS7 envelope in binary DER format
     * @param string $recipientPublic   Recipient's certificate in PEM format
     * @param string $recipientPrivate  Recipient's private key in PEM format
     *
     * @return string Decrypted envelope content
     *
     * @throws ProcessFailedException When decryption command fails
     */
    public function decryptPkcs7Envelope(string $scepPkcs7Envelope, string $recipientPublic, string $recipientPrivate): string
    {
        $scepResponsePath = $this->fileManager->dumpTmpFile($scepPkcs7Envelope);
        $recipientPublicPath = $this->fileManager->dumpTmpFile($recipientPublic);
        $recipientPrivatePath = $this->fileManager->dumpTmpFile($recipientPrivate);

        $content = $this->fileManager->getTmpFile();

        // openssl cms -decrypt -in scep_pkcs7.pem -inform der -inkey ca.key -recip ca.crt -rctform PEM -outform DER -out csrscep.req
        try {
            $command = [
               'openssl',
                'cms',
                '-decrypt',
                '-in',
                $scepResponsePath,
                '-inform',
                'DER',
                '-out',
                $content,
                '-inkey',
                $recipientPrivatePath,
                '-recip',
                $recipientPublicPath,
            ];

            $this->runCommand($command);

            return file_get_contents($content);
        } finally {
            $this->fileManager->removeFile($scepResponsePath);
            $this->fileManager->removeFile($recipientPublicPath);
            $this->fileManager->removeFile($recipientPrivatePath);
            $this->fileManager->removeFile($content);
        }
    }

    /**
     * Extracts and verifies SCEP response message using CA certificate.
     *
     * Verifies the cryptographic signature of the SCEP response message to ensure it was
     * signed by the CA server. Requires the CA's public certificate to verify the signature.
     *
     * @param string $scepResponse Complete SCEP response message in binary DER format
     * @param string $caPublic     CA public certificate in PEM format for signature verification
     *
     * @return string Extracted and verified SCEP response message
     *
     * @throws ProcessFailedException When verification command fails
     * @throws ProviderException      When signature verification fails
     */
    public function extractVerifiedScepResponseMessage(string $scepResponse, string $caPublic): string
    {
        $scepResponsePath = $this->fileManager->dumpTmpFile($scepResponse);
        $caPublicPath = $this->fileManager->dumpTmpFile($caPublic);

        $scepResponseMessagePath = $this->fileManager->getTmpFile();

        // openssl smime -verify -in $scepResponsePath -inform DER -out $scepResponseMessagePath -CAfile $caPublicPath
        try {
            $command = [
               'openssl',
                'smime',
                '-verify',
                '-in',
                $scepResponsePath,
                '-inform',
                'DER',
                '-out',
                $scepResponseMessagePath,
                '-CAfile',
                $caPublicPath,
            ];

            $this->runCommand($command);

            return file_get_contents($scepResponseMessagePath);
        } finally {
            $this->fileManager->removeFile($scepResponsePath);
            $this->fileManager->removeFile($caPublicPath);
            $this->fileManager->removeFile($scepResponseMessagePath);
        }
    }

    /**
     * Extracts SCEP envelope from SCEP response message.
     *
     * The SCEP envelope contains the encrypted certificate that was signed by the SCEP server.
     * The envelope is encrypted with the recipient's public key and must be decrypted using
     * the matching private key.
     *
     * @param string $scepResponseMessage Verified SCEP response message
     * @param string $selfSignPrivate     Private key in PEM format to decrypt the envelope
     *
     * @return string Extracted SCEP envelope containing encrypted signed certificate
     *
     * @throws ProcessFailedException When envelope extraction fails
     */
    public function extractScepEnvelope(string $scepResponseMessage, string $selfSignPrivate): string
    {
        $scepResponseMessagePath = $this->fileManager->dumpTmpFile($scepResponseMessage);
        $selfSignPrivatePath = $this->fileManager->dumpTmpFile($selfSignPrivate);

        $scepResponseEnvelopePath = $this->fileManager->getTmpFile();

        // openssl smime -decrypt -in $scepResponseMessagePath -inform DER -out $scepResponseEnvelopePath -inkey $selfSignPrivatePath -outform DER
        try {
            $command = [
               'openssl',
                'smime',
                '-decrypt',
                '-in',
                $scepResponseMessagePath,
                '-inform',
                'DER',
                '-out',
                $scepResponseEnvelopePath,
                '-inkey',
                $selfSignPrivatePath,
                '-outform',
                'DER',
            ];

            $this->runCommand($command);

            return file_get_contents($scepResponseEnvelopePath);
        } finally {
            $this->fileManager->removeFile($scepResponseMessagePath);
            $this->fileManager->removeFile($selfSignPrivatePath);
            $this->fileManager->removeFile($scepResponseEnvelopePath);
        }
    }

    /**
     * Extracts certificates from SCEP envelope.
     *
     * Parses PKCS7 envelope to extract X509 certificates. If envelope contains multiple
     * certificates, all are returned concatenated. Normalizes output to ensure clean PEM format.
     *
     * @param string $scepEnvelope SCEP envelope content (can be DER or PEM encoded)
     * @param string $inform       Input format: 'DER' (default) or 'PEM'
     *
     * @return string Extracted certificate(s) in PEM format, normalized and cleaned
     *
     * @throws ProcessFailedException When certificate extraction fails
     */
    public function extractCertificatesFromScepEnvelope(string $scepEnvelope, string $inform = 'DER'): string
    {
        $scepEnvelopePath = $this->fileManager->dumpTmpFile($scepEnvelope);

        // openssl pkcs7 -in $scepEnvelopePath -inform DER -print_certs
        try {
            $command = [
               'openssl',
                'pkcs7',
                '-in',
                $scepEnvelopePath,
                '-inform',
                $inform,
                '-print_certs',
            ];

            $certificatePem = $this->runCommand($command);

            if ($certificatePem) {
                // Making sure that only PEM certificate is returned - openssl in alpine is returning extra lines
                $certificatePem = $this->normalizeCertificate($certificatePem);
            }

            return $certificatePem;
        } finally {
            $this->fileManager->removeFile($scepEnvelopePath);
        }
    }

    /**
     * Normalizes certificate to ensure clean PEM format.
     *
     * Removes extra lines and formatting artifacts that may be added by OpenSSL in Alpine Linux.
     * Ensures the certificate is properly framed with standard BEGIN/END markers.
     *
     * @param string $certificateContent Raw certificate content with potential extra formatting
     *
     * @return string Normalized PEM-encoded certificate with proper newline terminator
     */
    protected function normalizeCertificate(string $certificateContent): string
    {
        $begin = '-----BEGIN CERTIFICATE-----';
        $end = '-----END CERTIFICATE-----';

        $certificateContent = substr($certificateContent, strpos($certificateContent, $begin));
        $certificateContent = substr($certificateContent, 0, strrpos($certificateContent, $end) + strlen($end));

        return $certificateContent."\n";
    }

    /**
     * Extracts SCEP message type from SCEP response.
     *
     * @param string $message SCEP response message
     *
     * @return string|null SCEP message type value (e.g., '3' for CertRep), or null if not found
     */
    public function getScepMessageType(string $message): ?string
    {
        return $this->getOidContentFromMessage(self::OID_SCEP_MESSAGE_TYPE, $message);
    }

    /**
     * Extracts PKI status from SCEP response.
     *
     * @param string $message SCEP response message
     *
     * @return string|null PKI status value: '0' for SUCCESS, '2' for FAILURE, '3' for PENDING, or null if not found
     */
    public function getScepPkiStatus(string $message): ?string
    {
        return $this->getOidContentFromMessage(self::OID_SCEP_PKI_STATUS, $message);
    }

    /**
     * Extracts failure information from SCEP response.
     *
     * @param string $message SCEP response message
     *
     * @return string|null Failure information encoded value, or null if not found
     */
    public function getScepFailInfo(string $message): ?string
    {
        return $this->getOidContentFromMessage(self::OID_SCEP_FAIL_INFO, $message);
    }

    /**
     * Extracts human-readable failure text from SCEP response.
     *
     * @param string $message SCEP response message
     *
     * @return string|null Failure description text, or null if not found
     */
    public function getScepFailInfoText(string $message): ?string
    {
        return $this->getOidContentFromMessage(self::OID_SCEP_FAIL_INFO_TEXT, $message);
    }

    /**
     * Extracts sender nonce from SCEP message.
     *
     * The nonce is used to prevent replay attacks in SCEP communication.
     *
     * @param string $message SCEP message
     *
     * @return string|null Sender nonce value, or null if not found
     */
    public function getScepSenderNonce(string $message): ?string
    {
        return $this->getOidContentFromMessage(self::OID_SCEP_SENDER_NONCE, $message);
    }

    /**
     * Extracts transaction ID from SCEP message.
     *
     * The transaction ID links the SCEP response to the original request.
     *
     * @param string $message SCEP message
     *
     * @return string|null Transaction ID value, or null if not found
     */
    public function getScepTransactionId(string $message): ?string
    {
        return $this->getOidContentFromMessage(self::OID_SCEP_TRANSACTION_ID, $message);
    }

    /**
     * Extracts enveloped PKCS7 content from SCEP message.
     *
     * @param string $message SCEP message
     *
     * @return string|null Enveloped PKCS7 content, or null if not found
     */
    public function getScepEnvelopedPkcs7Content(string $message): ?string
    {
        return $this->getOidContentFromMessage(self::OID_SCEP_ENVELOPED_PKCS7, $message);
    }

    /**
     * Extracts enveloped request PKCS7 content from SCEP message.
     *
     * @param string $message SCEP message
     *
     * @return string|null Enveloped request PKCS7 content, or null if not found
     */
    public function getScepEnvelopedRequestPkcs7Content(string $message): ?string
    {
        return $this->getOidContentFromMessage(self::OID_SCEP_ENVELOPED_REQUEST_PKCS7, $message);
    }

    /**
     * Extracts OID content from SCEP message.
     *
     * Parses ASN.1 BER-encoded message structure to find and extract value of specified OID.
     *
     * @param string $oid     Object Identifier to search for (e.g., '2.16.840.1.113733.1.9.2' for message type)
     * @param string $message SCEP message in binary DER format
     *
     * @return string|null Content associated with the OID, or null if OID not found
     */
    public function getOidContentFromMessage(string $oid, string $message): ?string
    {
        $decodedBer = ASN1::decodeBER(ASN1::extractBER($message));

        if (!is_array($decodedBer)) {
            return null;
        }

        return $this->getOidContent($oid, $decodedBer);
    }

    /**
     * Recursively searches ASN.1 BER structure for OID and extracts its content.
     *
     * @param string     $oid       Object Identifier to search for
     * @param array      $ber       ASN.1 BER-decoded array structure
     * @param array|null $parentBer Parent BER structure for context (used internally for recursion)
     *
     * @return string|null Content associated with the OID, or null if not found in current path
     */
    protected function getOidContent(string $oid, array $ber, ?array $parentBer = null): ?string
    {
        foreach ($ber as $key => $value) {
            if ('content' == $key && $value == $oid) {
                if (isset($parentBer[1]['content'][0]['content'])) {
                    return $parentBer[1]['content'][0]['content'];
                }
            }

            if (is_array($value)) {
                $oidContent = $this->getOidContent($oid, $value, $ber);

                if (null !== $oidContent) {
                    return $oidContent;
                }
            }
        }

        return null;
    }
}
