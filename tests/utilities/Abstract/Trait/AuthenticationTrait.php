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

namespace Tests\Utilities\Abstract\Trait;

use Carve\ApiBundle\Helper\Arr;

/**
 * Code separated for readability.
 * To be used only with AbstractTestCase class.
 */
trait AuthenticationTrait
{
    protected bool $digestAuth = false;
    protected ?string $digestUsername = null;
    protected ?string $digestPassword = null;

    public function loginApi(string $username, string $password): void
    {
        $this->clearAuthorization();

        $this->jsonPost('/web/api/authentication/login_check', [
            'username' => $username,
            'password' => $password,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseJson();

        $response = $this->getResponseContentAsArray();
        $token = Arr::get($response, 'token');

        $this->assertIsString($token);

        $client = $this->getHttpClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer '.$token);
    }

    public function loginX509Auth(string $certificate, string $key): void
    {
        $this->clearAuthorization();

        $client = $this->getHttpClient();
        $client->setServerParameter('CLIENT_SSL_CERT', \urlencode($certificate));
    }

    public function loginBasicAuth(string $username, string $password): void
    {
        $this->clearAuthorization();

        $client = $this->getHttpClient();
        $client->setServerParameter('PHP_AUTH_USER', $username);
        $client->setServerParameter('PHP_AUTH_PW', $password);
    }

    public function loginDigestAuth(string $username, string $password): void
    {
        $this->clearAuthorization();

        $this->setDigestAuth(true);
        $this->setDigestUsername($username);
        $this->setDigestPassword($password);
    }

    public function clearAuthorization(): void
    {
        $this->setDigestAuth(false);
        $this->setDigestUsername(null);
        $this->setDigestPassword(null);

        // This function has to clear any changes made to client by loginX() functions
        $this->getHttpClient()->setServerParameters([]);
    }

    // >> Digest authentication helper methods
    public function getDigestUsername(): ?string
    {
        return $this->digestUsername;
    }

    public function setDigestUsername(?string $digestUsername)
    {
        $this->digestUsername = $digestUsername;
    }

    public function getDigestPassword(): ?string
    {
        return $this->digestPassword;
    }

    public function setDigestPassword(?string $digestPassword)
    {
        $this->digestPassword = $digestPassword;
    }

    public function getDigestAuth(): bool
    {
        return $this->digestAuth;
    }

    public function setDigestAuth(bool $digestAuth)
    {
        $this->digestAuth = $digestAuth;
    }

    /**
     * @return array|null Array of parameters extracted from www-authenticate header. Returns null when parameters could not be extracted.
     */
    public function extractDigestAuthorizationParameters(): ?array
    {
        $digestHeader = $this->getResponse()->headers->get('www-authenticate');

        if (!$digestHeader) {
            return null;
        }

        if (!\str_starts_with($digestHeader, 'Digest ')) {
            return null;
        }

        $digestHeaderValue = \substr($digestHeader, \strlen('Digest '));
        $properties = \explode(', ', $digestHeaderValue);
        $parameters = [];

        foreach ($properties as $property) {
            list($key, $value) = explode('=', $property);
            $value = \str_replace('"', '', $value);
            $parameters[$key] = $value;
        }

        return $parameters;
    }

    public function isDigestAuthorization(): bool
    {
        if (!$this->getDigestAuth()) {
            return false;
        }

        if (!$this->getDigestUsername()) {
            return false;
        }

        if (!$this->getDigestPassword()) {
            return false;
        }

        if (401 !== $this->getResponseStatusCode()) {
            return false;
        }

        $parameters = $this->extractDigestAuthorizationParameters();
        if (null === $parameters) {
            return false;
        }

        if (!Arr::get($parameters, 'realm')) {
            return false;
        }

        if (!Arr::get($parameters, 'nonce')) {
            return false;
        }

        // SMART EMS uses qop
        if (!Arr::get($parameters, 'qop')) {
            return false;
        }

        // SMART EMS supports qop=auth
        if ('auth' !== Arr::get($parameters, 'qop')) {
            return false;
        }

        return true;
    }

    public function includeDigestAuthorization(): void
    {
        $parameters = $this->extractDigestAuthorizationParameters();

        $cnonceString = md5('random'.time());
        $ncString = '00000001';
        $responseStringAuth = md5($this->getDigestUsername().':'.$parameters['realm'].':'.$this->getDigestPassword());
        $responseStringUri = md5($this->getRequestMethod().':'.$this->getRequestUrl());
        $responseString = md5($responseStringAuth.':'.$parameters['nonce'].':'.$ncString.':'.$cnonceString.':'.$parameters['qop'].':'.$responseStringUri);
        $digestResponse = [
            'username' => $this->getDigestUsername(),
            'uri' => $this->getRequestUrl(),
            'response' => $responseString,
            'cnonce' => $cnonceString,
            'nc' => $ncString,
        ];

        $digestResponse = array_merge($parameters, $digestResponse);

        $digestString = null;
        foreach ($digestResponse as $key => $value) {
            if (null == $digestString) {
                $digestString = 'Digest ';
            } else {
                $digestString .= ', ';
            }
            $digestString .= $key.'="'.$value.'"';
        }

        $this->getHttpClient()->setServerParameter('PHP_AUTH_DIGEST', $digestString);
    }

    public function clearDigestAuthorization(): void
    {
        $this->getHttpClient()->setServerParameters([]);
    }
    // << Digest authentication helper methods
}
