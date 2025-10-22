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
use App\Enum\PkiKeyLength;
use App\Exception\ProviderException;
use App\Provider\Interface\PkiProviderInterface;
use App\Service\FileManager;
use App\Trait\LogsCollectorTrait;
use Carve\ApiBundle\Helper\Arr;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ScepPkiProvider implements PkiProviderInterface
{
    use LogsCollectorTrait;

    protected ProviderHttpClient $httpClient;

    /**
     * @param string $projectDir                 directory for provider to find needed commands (/bin/scep)
     * @param string $certificateRequestDir      directory for provider to create temporary files (provider should remove created folders and files)
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
        protected string $certificateRequestDir,
        protected FileManager $fileManager,
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
        $getCaResponse = $this->httpClient->get($this->scepUrl, $data);

        if ('' === $getCaResponse) {
            throw new ProviderException($this->addLogError('log.scepPkiProvider.getCaCertificate.invalidResponse'));
        }

        $encodedCaCertificate = base64_encode($getCaResponse);

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

        if (null === $caCertificate) {
            throw new ProviderException($this->addLogCritical('log.scepPkiProvider.getCaCertificate.caUnavailable'));
        }

        $caCertificateData = openssl_x509_parse($caCertificate);
        if (false === $caCertificateData || !isset($caCertificateData['subject']) || !is_array($caCertificateData['subject'])) {
            throw new ProviderException($this->addLogCritical('log.scepPkiProvider.getCaCertificate.caInvalid'));
        }

        return $caCertificate;
    }

    // Method signs CSR $csr via SCEP using $caCertificatePem and returns signed certificate
    public function signCsr(PkiHashAlgorithm $hashAlgorithm, PkiKeyLength $keyLength, string $caCertificatePem, \OpenSSLCertificateSigningRequest $csr): string
    {
        $csrDataArray = openssl_csr_get_subject($csr);

        // This value is set by PkiProvidersManager
        $certificateSubject = Arr::get($csrDataArray, 'CN', 'default');

        $selfSignDn = [
            'commonName' => 'selfSigned_'.$certificateSubject,
        ];

        if (Arr::has($csrDataArray, 'C')) {
            $selfSignDn['countryName'] = Arr::get($csrDataArray, 'C');
        }

        if (Arr::has($csrDataArray, 'ST')) {
            $selfSignDn['stateOrProvinceName'] = Arr::get($csrDataArray, 'ST');
        }

        if (Arr::has($csrDataArray, 'L')) {
            $selfSignDn['localityName'] = Arr::get($csrDataArray, 'L');
        }

        if (Arr::has($csrDataArray, 'O')) {
            $selfSignDn['organizationName'] = Arr::get($csrDataArray, 'O');
        }

        $defaultConfigArgs = [
            'digest_alg' => $hashAlgorithm->value,
            'private_key_bits' => $keyLength->value,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $requestTTL = 365; // 365 days

        // Creation of privateKey to self sign SCEP envelope
        $selfSignPrivate = openssl_pkey_new($defaultConfigArgs);
        $selfSignCsr = openssl_csr_new($selfSignDn, $selfSignPrivate, $defaultConfigArgs);

        $selfSignPublic = openssl_csr_sign($selfSignCsr, null, $selfSignPrivate, $requestTTL, $defaultConfigArgs);

        $tmpDir = $this->createTmpDir();
        $csrPath = $tmpDir.'/'.$certificateSubject.'.csr';
        openssl_csr_export_to_file($csr, $csrPath);

        $selfSignPublicPath = $tmpDir.'/'.$certificateSubject.'_selfSigned.crt';
        openssl_x509_export_to_file($selfSignPublic, $selfSignPublicPath);

        $selfSignPrivatePath = $tmpDir.'/'.$certificateSubject.'_selfSigned.key';
        openssl_pkey_export_to_file($selfSignPrivate, $selfSignPrivatePath);

        $scepRequestPath = $tmpDir.'/scep_req.pem';
        $scepResponsePath = $tmpDir.'/scep_response.pem';
        $caCertificatePath = $tmpDir.'/ca.crt';

        file_put_contents($caCertificatePath, $caCertificatePem);
        // save CA cert to $caCertificatePath

        $output = $this->runCommand([$this->projectDir.'/bin/scep',  $selfSignPublicPath, $selfSignPrivatePath, $caCertificatePath, $csrPath, $scepRequestPath]);

        $scepRequest = file_get_contents($scepRequestPath);
        if (!$scepRequest) {
            $this->fileManager->remove($tmpDir);
            throw new ProviderException($this->addLogError('log.scepPkiProvider.signCsr.certificateRequestFailed', ['url' => $this->scepUrl, 'exceptionMessage' => $output ? $output : 'N/A']));
        }

        $data = [
            'operation' => 'PKIOperation',
            'message' => $scepRequest,
        ];

        try {
            // GET request with basic auth
            $scepResponse = $this->httpClient->get($this->scepUrl, $data);
        } catch (ProviderException $exception) {
            // todo test this case
            $this->fileManager->remove($tmpDir);
            $exception->addLogModel($this->addLogError('log.scepPkiProvider.signCsr.certificateResponseFailed', ['url' => $this->scepUrl]));
            throw $exception;
        }

        file_put_contents($scepResponsePath, $scepResponse);
        $verified = $this->runCommand(['openssl', 'smime', '-verify', '-in', $scepResponsePath, '-inform', 'DER', '-out', $scepResponsePath.'.msg', '-CAfile', $caCertificatePath]);

        if (!$this->fileManager->getFilesize($scepResponsePath.'.msg')) {
            $this->fileManager->remove($tmpDir);
            throw new ProviderException($this->addLogError('log.scepPkiProvider.signCsr.verificationFailed'));
        }

        $output = $this->runCommand(['openssl', 'smime', '-decrypt', '-in', $scepResponsePath.'.msg', '-inform', 'DER', '-out', $scepResponsePath.'.pkcs7', '-inkey',  $selfSignPrivatePath, '-outform', 'der']);

        if (!$this->fileManager->getFilesize($scepResponsePath.'.pkcs7')) {
            $this->fileManager->remove($tmpDir);
            throw new ProviderException($this->addLogError('log.scepPkiProvider.signCsr.decryptionFailed'));
        }

        $certificatePem = $this->runCommand(['openssl', 'pkcs7', '-in', $scepResponsePath.'.pkcs7', '-inform', 'DER', '-print_certs']);
        if (!$certificatePem) {
            $this->fileManager->remove($tmpDir);
            throw new ProviderException($this->addLogError('log.scepPkiProvider.signCsr.decodingCertificateFailed'));
        }
        // Making sure that only PEM certificate is saved
        $certificatePem = $this->processCertificate($certificatePem);

        $this->fileManager->remove($tmpDir);

        if (!$certificatePem) {
            throw new ProviderException($this->addLogError('log.scepPkiProvider.signCsr.extractionFailed'));
        }

        return $certificatePem;
    }

    // Method certificate with provided serialNumber via SCEP
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
     * @param $encodedCertificate base64 encoded certificate as string (without -----BEGIN CERTIFICATE----- and -----END CERTIFICATE-----)
     *
     * @return ?string Returns null when $encodedCertificate could not been parsed, otherwise string with certificate contents (including -----BEGIN CERTIFICATE----- and -----END CERTIFICATE-----)
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
     * @param $encodedCertificate base64 encoded certificate as string (without -----BEGIN CERTIFICATE----- and -----END CERTIFICATE-----)
     *
     * @return ?string Returns null when $encodedCertificate could not been parsed, otherwise string with contents of all certificates (including -----BEGIN CERTIFICATE----- and -----END CERTIFICATE-----)
     */
    protected function parsePCKS7Certificate(string $encodedCertificate): ?string
    {
        $tmpDir = $this->createTmpDir();

        $pkcs7Path = $tmpDir.'/pkcs7.crt';
        $pkcs7 = "-----BEGIN PKCS7-----\n".$encodedCertificate.'-----END PKCS7-----';
        file_put_contents($pkcs7Path, $pkcs7);

        $output = $this->runCommand(['openssl', 'pkcs7', '-in', $pkcs7Path, '-print_certs']);
        if (!$output) {
            $this->fileManager->remove($tmpDir);

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

        $this->fileManager->remove($tmpDir);

        if (0 === count($certificates)) {
            return null;
        }

        return implode('', $certificates);
    }

    // Making sure that only PEM certificate is retured - openssl in alpine is returning extra lines
    protected function processCertificate(string $certificateContent): string
    {
        $begin = '-----BEGIN CERTIFICATE-----';
        $end = '-----END CERTIFICATE-----';

        $certificateContent = substr($certificateContent, strpos($certificateContent, $begin));
        $certificateContent = substr($certificateContent, 0, strrpos($certificateContent, $end) + strlen($end));

        return $certificateContent."\n";
    }

    protected function runCommand(array $command): ?string
    {
        $process = new Process($command);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            $exception = new ProcessFailedException($process);

            // Not throwing exception because failed command execution need to be handled in this class differently depending on use case
            $this->addLogCritical('log.scepPkiProvider.consoleCommandFailed', [
                'commandString' => \implode(' ', $command),
                'exceptionMessage' => $exception->getMessage(),
            ]);

            return null;
        }

        return $process->getOutput();
    }

    protected function createTmpDir(): string
    {
        $uuid = Uuid::v4()->toRfc4122();
        $tmpDir = $this->certificateRequestDir.$uuid;

        $this->fileManager->mkdir($tmpDir, 0700);

        return $tmpDir;
    }
}
