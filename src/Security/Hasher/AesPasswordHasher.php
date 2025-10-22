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

namespace App\Security\Hasher;

use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\LegacyPasswordHasherInterface;

class AesPasswordHasher implements LegacyPasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    /**
     * @var string
     */
    protected $aesPasswordEncryptionKey;

    public const CIPHER = 'AES-128-CBC';

    public function __construct(string $aesPasswordEncryptionKey)
    {
        $this->aesPasswordEncryptionKey = $aesPasswordEncryptionKey;
    }

    public function getRequiredSaltLength()
    {
        return openssl_cipher_iv_length(self::CIPHER);
    }

    /**
     * Checks if a password hash would benefit from rehashing.
     */
    public function needsRehash(string $hashedPassword): bool
    {
        // For now we do not need to rehash - maybe if hashing algorithms will be updated
        return false;
    }

    public function hash(string $plainPassword, null|string $salt = null): string
    {
        if ($this->isPasswordTooLong($plainPassword)) {
            throw new InvalidPasswordException();
        }

        $ivlen = $this->getRequiredSaltLength();

        return openssl_encrypt($plainPassword, self::CIPHER, $this->aesPasswordEncryptionKey, 0, $salt ? substr($salt, 0, $ivlen) : '');
    }

    public function unHash($hashedPassword, null|string $salt = null)
    {
        $ivlen = $this->getRequiredSaltLength();

        return openssl_decrypt($hashedPassword, self::CIPHER, $this->aesPasswordEncryptionKey, 0, $salt ? substr($salt, 0, $ivlen) : '');
    }

    public function verify(string $hashedPassword, string $plainPassword, null|string $salt = null): bool
    {
        if ('' === $plainPassword || $this->isPasswordTooLong($plainPassword)) {
            return false;
        }

        return $hashedPassword == $this->hash($plainPassword, $salt);
    }
}
