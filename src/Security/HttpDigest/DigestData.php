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

namespace App\Security\HttpDigest;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class DigestData
{
    private $elements = [];
    private $header;
    private $nonceExpiryTime;

    public function __construct($header)
    {
        $this->header = $header;
        preg_match_all('/(\w+)=("((?:[^"\\\\]|\\\\.)+)"|([^\s,$]+))/', $header, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (isset($match[1]) && isset($match[3])) {
                $this->elements[$match[1]] = isset($match[4]) ? $match[4] : $match[3];
            }
        }
    }

    public function getResponse()
    {
        return $this->elements['response'];
    }

    public function getUsername()
    {
        return strtr($this->elements['username'], ['\\"' => '"', '\\\\' => '\\']);
    }

    public function validateAndDecode($entryPointKey, $expectedRealm)
    {
        if ($keys = array_diff(['username', 'realm', 'nonce', 'uri', 'response'], array_keys($this->elements))) {
            throw new BadCredentialsException(sprintf('Missing mandatory digest value; received header "%s" (%s)', $this->header, implode(', ', $keys)));
        }

        if ('auth' === $this->elements['qop']) {
            if (!isset($this->elements['nc']) || !isset($this->elements['cnonce'])) {
                throw new BadCredentialsException(sprintf('Missing mandatory digest value; received header "%s"', $this->header));
            }
        }

        if ($expectedRealm !== $this->elements['realm']) {
            throw new BadCredentialsException(sprintf('Response realm name "%s" does not match system realm name of "%s".', $this->elements['realm'], $expectedRealm));
        }

        if (false === $nonceAsPlainText = base64_decode($this->elements['nonce'])) {
            throw new BadCredentialsException(sprintf('Nonce is not encoded in Base64; received nonce "%s".', $this->elements['nonce']));
        }

        $nonceTokens = explode(':', $nonceAsPlainText);

        if (2 !== count($nonceTokens)) {
            throw new BadCredentialsException(sprintf('Nonce should have yielded two tokens but was "%s".', $nonceAsPlainText));
        }

        $this->nonceExpiryTime = $nonceTokens[0];

        if (md5($this->nonceExpiryTime.':'.$entryPointKey) !== $nonceTokens[1]) {
            throw new BadCredentialsException(sprintf('Nonce token compromised "%s".', $nonceAsPlainText));
        }
    }

    public function calculateServerDigest($password, $httpMethod)
    {
        $a2Md5 = md5(strtoupper($httpMethod).':'.$this->elements['uri']);
        $a1Md5 = md5($this->elements['username'].':'.$this->elements['realm'].':'.$password);

        $digest = $a1Md5.':'.$this->elements['nonce'];
        if (!isset($this->elements['qop'])) {
        } elseif ('auth' === $this->elements['qop']) {
            $digest .= ':'.$this->elements['nc'].':'.$this->elements['cnonce'].':'.$this->elements['qop'];
        } else {
            throw new \InvalidArgumentException('This method does not support a qop: "%s".', $this->elements['qop']);
        }
        $digest .= ':'.$a2Md5;

        return md5($digest);
    }

    public function isNonceExpired()
    {
        return $this->nonceExpiryTime < microtime(true);
    }
}
