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

namespace App\Service;

class EncryptionManager
{
    public const CIPHER = 'AES-256-CBC';
    public const ENCRYPTION_KEY = 'fc1a43748a5a1f4f16d822a9fb95e8a7';
    public const IV = '6ba1d459ad0fa67d0de82415c43b9c1e';

    public function getCrc32bCertificateChecksum(string $decryptedText)
    {
        return hash('crc32b', $decryptedText);
    }

    public function decrypt(string $text): string
    {
        $ivlen = openssl_cipher_iv_length(self::CIPHER);

        return openssl_decrypt(base64_decode($text), self::CIPHER, substr(sha1(self::ENCRYPTION_KEY), 5, 32), 0, substr(self::IV, 0, $ivlen));
    }

    public function encrypt(string $text): string
    {
        $ivlen = openssl_cipher_iv_length(self::CIPHER);

        return base64_encode(openssl_encrypt($text, self::CIPHER, substr(sha1(self::ENCRYPTION_KEY), 5, 32), 0, substr(self::IV, 0, $ivlen)));
    }

    public function encodeCertificateToOneLine(string $content): string
    {
        $cleanContent = str_replace(["\r", "\n"], ['', ''], $content);
        $contentExploded = explode('-----', $cleanContent);

        for ($key = 2; $key < count($contentExploded); $key += 4) {
            $certContent = isset($contentExploded[$key]) ? $contentExploded[$key] : '';
            // Leave last 64 or less signs
            // floor() returns the next lowest integer value (as float) by rounding down num if necessary.
            // Cast to int to avoid issues with substr_replace()
            $steps = (int) floor((strlen($certContent) - 1) / 64);

            // Add at the end
            $certContent = substr_replace($certContent, '\0A', strlen($certContent), 0);
            for ($i = $steps; $i >= 0; --$i) {
                // Assuming 3 steps = Add at position 192, 128, 64 and 0
                $certContent = substr_replace($certContent, '\0A', $i * 64, 0);
            }

            $contentExploded[$key] = $certContent;
        }
        $certificate = implode('-----', $contentExploded);

        return str_replace('-----END CERTIFICATE----------BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----\\0A-----BEGIN CERTIFICATE-----', $certificate).'\\0A';
    }

    public function getRootCaFromCaChain(string $chain): string
    {
        $chainCerts = explode('-----END CERTIFICATE-----', $chain);

        for ($i = count($chainCerts) - 1; $i >= 0; --$i) {
            if (false !== strpos($chainCerts[$i], '-----BEGIN CERTIFICATE-----')) {
                return $chainCerts[$i]."-----END CERTIFICATE-----\n";
            }
        }

        return '';
    }

    public function convertCertificateForConfig(string $text): string
    {
        $resultArray = [];
        $littleEndian = unpack('H*', $text)[1];
        $spaceSeparated = chunk_split($littleEndian, 4, ' ');
        $array = explode(' ', $spaceSeparated);
        $separator = "\n   ";

        foreach ($array as $item) {
            $str1 = substr($item, 0, 2);
            $str2 = substr($item, 2);

            if ($str1 && $str2) {
                $result = $str2.$str1;
                $resultArray[] = $result;
            } elseif ($str1) {
                $resultArray[] = '00'.$str1;
            } elseif ($str2) {
                $resultArray[] = '00'.$str2;
            }
        }

        $result = array_reduce(
            array_map(
                function ($i) use ($separator) {
                    return 8 == count($i) ? array_merge($i, [$separator]) : $i;
                },
                array_chunk($resultArray, 8)
            ),
            function ($r, $i) {
                return array_merge($r, $i);
            },
            []
        );

        $result = implode(' ', $result);

        return $result;
    }
}
