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

namespace Tests\Utilities\DeviceCommunicationAbstract\Trait;

use App\Service\Helper\SymfonyDirTrait;
use Carve\ApiBundle\Helper\Arr;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Tests\Utilities\DeviceCommunicationAbstract\Enum\DeviceCertificateState;

/**
 * Trait provides certificates, keys and ca's based on DeviceCertificateState. Files are generated if non-existent or old (1 month),
 * otherwise are cached in var/cache/certs folder.
 */
trait X509AuthenticationCertificateFactoryTrait
{
    use SymfonyDirTrait;

    // Static properties to make sure that they are correctly cached
    protected static $certificateArray = [];
    protected static $privateKeyArray = [];
    protected static $caArray = [];

    public function getSystemCertificate(DeviceCertificateState $certificateState): ?string
    {
        $key = $this->getSystemKey($certificateState);
        $this->loadKeyToArray($key);

        return Arr::get($this::$certificateArray, $key->value);
    }

    public function getSystemPrivateKey(DeviceCertificateState $certificateState): ?string
    {
        $key = $this->getSystemKey($certificateState);
        $this->loadKeyToArray($key);

        return Arr::get($this::$privateKeyArray, $key->value);
    }

    public function getSystemCaCertificate(DeviceCertificateState $certificateState): ?string
    {
        $key = $this->getSystemKey($certificateState);
        $this->loadKeyToArray($key);

        return Arr::get($this::$caArray, $key->value);
    }

    public function getRequestCertificate(DeviceCertificateState $certificateState): ?string
    {
        $key = $this->getRequestKey($certificateState);
        $this->loadKeyToArray($key);

        return Arr::get($this::$certificateArray, $key->value);
    }

    public function getRequestPrivateKey(DeviceCertificateState $certificateState): ?string
    {
        $key = $this->getRequestKey($certificateState);
        $this->loadKeyToArray($key);

        return Arr::get($this::$privateKeyArray, $key->value);
    }

    public function getRequestCaCertificate(DeviceCertificateState $certificateState): ?string
    {
        $key = $this->getRequestKey($certificateState);
        $this->loadKeyToArray($key);

        return Arr::get($this::$caArray, $key->value);
    }

    // Method calculates key in traits arrays that should be returned
    // Method uses some of DeviceCertificateState keys
    private function getSystemKey(DeviceCertificateState $certificateState): DeviceCertificateState
    {
        switch ($certificateState) {
            case DeviceCertificateState::VALID_SELF_SIGNED:
                return DeviceCertificateState::VALID_SELF_SIGNED;
            case DeviceCertificateState::EXPIRED:
                return DeviceCertificateState::EXPIRED;
            case DeviceCertificateState::NOT_IN_DEVICE_TYPE_CREDENTIAL:
            case DeviceCertificateState::VALID:
            case DeviceCertificateState::DIFFERENT:
            default:
                return DeviceCertificateState::VALID;
        }
    }

    // Method calculates key in traits arrays that should be returned
    // Method uses some of DeviceCertificateState keys
    private function getRequestKey(DeviceCertificateState $certificateState): DeviceCertificateState
    {
        switch ($certificateState) {
            case DeviceCertificateState::VALID_SELF_SIGNED:
                return DeviceCertificateState::VALID_SELF_SIGNED;
            case DeviceCertificateState::EXPIRED:
                return DeviceCertificateState::EXPIRED;
            case DeviceCertificateState::DIFFERENT:
                return DeviceCertificateState::DIFFERENT;
            case DeviceCertificateState::NOT_IN_DEVICE_TYPE_CREDENTIAL:
            case DeviceCertificateState::VALID:
            default:
                return DeviceCertificateState::VALID;
        }
    }

    // Method generates or loads certificate, key and ca (or nothing if already cached) into traits arrays
    // Method uses some of DeviceCertificateState keys
    private function loadKeyToArray(DeviceCertificateState $certificateState): void
    {
        // Certificate always has to exist if key is loaded, if exists means, it is already cached - nothing to do
        if (Arr::get($this::$certificateArray, $certificateState->value)) {
            return;
        }

        $cacheDir = 'var/cache/certs';
        $fs = new Filesystem();

        $expectedFiles = [
          'certificate' => $cacheDir.'/'.$certificateState->value.'.crt',
          'privateKey' => $cacheDir.'/'.$certificateState->value.'.key',
          'ca' => $cacheDir.'/'.$certificateState->value.'.ca',
        ];

        if (DeviceCertificateState::VALID_SELF_SIGNED == $certificateState) {
            unset($expectedFiles['ca']);
        }

        try {
            if (!$fs->exists($cacheDir)) {
                $fs->mkdir($cacheDir);
            }
            if (!$this->isFileUpToDate($certificateState, $expectedFiles)) {
                $this->generateFiles($certificateState, $expectedFiles);
            }
            $this->loadFiles($certificateState, $expectedFiles);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    private function isFileUpToDate(DeviceCertificateState $certificateState, array $expectedFiles): bool
    {
        $fs = new Filesystem();

        $allowedModifyDateTime = (new \DateTime())->modify('-1 month')->format('u');

        foreach ($expectedFiles as $filename) {
            if (!$fs->exists($filename)) {
                return false;
            }
            $modifyDate = filemtime($filename);
            if (!$modifyDate) {
                return false;
            }
            if ($allowedModifyDateTime > $modifyDate) {
                return false;
            }
        }

        return true;
    }

    private function loadFiles(DeviceCertificateState $certificateState, array $expectedFiles): void
    {
        $fs = new Filesystem();
        foreach ($expectedFiles as $arrayName => $filename) {
            $content = file_get_contents($filename);
            switch ($arrayName) {
                case 'certificate':
                    $this::$certificateArray[$certificateState->value] = $content;
                    break;
                case 'privateKey':
                    $this::$privateKeyArray[$certificateState->value] = $content;
                    break;
                case 'ca':
                    $this::$caArray[$certificateState->value] = $content;
                    break;
                default:
                }
        }
    }

    private function runCommand(array $command): ?string
    {
        $process = new Process($command);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            $exception = new ProcessFailedException($process);

            return null;
        }

        return $process->getOutput();
    }

    private function generateFiles(DeviceCertificateState $certificateState, array $expectedFiles): void
    {
        switch ($certificateState) {
            case DeviceCertificateState::NOT_IN_DEVICE_TYPE_CREDENTIAL:
            case DeviceCertificateState::VALID:
                $this->runCommand(['openssl', 'genrsa',
                                    '-out', $expectedFiles['ca'].'.key',
                                    '2048', ]);

                $this->runCommand(['openssl', 'req', '-x509', '-new', '-nodes',
                                    '-key', $expectedFiles['ca'].'.key',
                                    '-out', $expectedFiles['ca'],
                                    '-subj', '/CN=validCa/C=DE',
                                ]);

                $this->runCommand(['openssl', 'req', '-new', '-nodes', '-newkey', 'rsa:2048',
                                    '-keyout', $expectedFiles['privateKey'],
                                    '-out', $expectedFiles['certificate'].'.csr',
                                    '-subj', '/CN=validCert/C=DE',
                                ]);

                $this->runCommand(['openssl', 'x509', '-req', '-days', '365',
                                    '-CAkey', $expectedFiles['ca'].'.key',
                                    '-CA', $expectedFiles['ca'],
                                    '-in', $expectedFiles['certificate'].'.csr',
                                    '-out', $expectedFiles['certificate'],
                                ]);
                break;

            case DeviceCertificateState::VALID_SELF_SIGNED:
                $this->runCommand(['openssl', 'genrsa',
                                    '-out', $expectedFiles['privateKey'],
                                    '2048',
                                ]);

                $this->runCommand(['openssl', 'req', '-new',
                                    '-key', $expectedFiles['privateKey'],
                                    '-out', $expectedFiles['certificate'].'.csr',
                                    '-subj', '/CN=validCertSelfSigned/C=DE',
                                ]);

                $this->runCommand(['openssl', 'x509', '-req', '-days', '365',
                                    '-signkey', $expectedFiles['privateKey'],
                                    '-in', $expectedFiles['certificate'].'.csr',
                                    '-out', $expectedFiles['certificate'],
                                    '-subj', '/CN=validCertSelfSigned/C=DE',
                                ]);

                break;
            case DeviceCertificateState::DIFFERENT:
                $this->runCommand(['openssl', 'genrsa',
                                    '-out', $expectedFiles['ca'].'.key',
                                    '2048',
                                ]);

                $this->runCommand(['openssl', 'req', '-x509', '-new', '-nodes',
                                    '-key', $expectedFiles['ca'].'.key',
                                    '-out', $expectedFiles['ca'],
                                    '-subj', '/CN=differentCa/C=DE',
                                ]);

                $this->runCommand(['openssl', 'req', '-new', '-nodes', '-newkey', 'rsa:2048',
                                    '-keyout', $expectedFiles['privateKey'],
                                    '-out', $expectedFiles['certificate'].'.csr',
                                    '-subj', '/CN=differentCert/C=DE',
                                ]);

                $this->runCommand(['openssl', 'x509', '-req', '-days', '365',
                                    '-CAkey', $expectedFiles['ca'].'.key',
                                    '-CA', $expectedFiles['ca'],
                                    '-in', $expectedFiles['certificate'].'.csr',
                                    '-out', $expectedFiles['certificate'],
                                        ]);
                break;
            case DeviceCertificateState::EXPIRED:
                $this->runCommand(['openssl', 'genrsa',
                                    '-out', $expectedFiles['ca'].'.key',
                                    '2048',
                                ]);

                $this->runCommand(['openssl', 'req', '-x509', '-new', '-nodes',
                                    '-key', $expectedFiles['ca'].'.key',
                                    '-out', $expectedFiles['ca'],
                                    '-subj', '/CN=expiredCertCa/C=DE',
                                ]);

                $this->runCommand(['openssl', 'req', '-new', '-nodes', '-newkey', 'rsa:2048',
                                    '-keyout', $expectedFiles['privateKey'],
                                    '-out', $expectedFiles['certificate'].'.csr',
                                    '-subj', '/CN=expiredCert/C=DE',
                                ]);

                $this->runCommand(['openssl', 'x509', '-req', '-days', '-1',
                                    '-CAkey', $expectedFiles['ca'].'.key',
                                    '-CA', $expectedFiles['ca'],
                                    '-in', $expectedFiles['certificate'].'.csr',
                                    '-out', $expectedFiles['certificate'],
                                ]);
                break;
            default:
            // Do nothing if null
            }
    }
}
