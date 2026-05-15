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

use App\Entity\Firmware;
use App\Enum\AuthenticationMethod;
use App\Enum\Feature;
use App\Enum\SourceType;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceTypeModel;

/**
 * Trait is to be used in test cases that extend AbstractDeviceCommunicationTestCase.
 * Trait provides helper methods to execute device communication tests
 * that focus on device firmware authentication handling during device communication.
 */
trait DeviceCommunicationFirmwareTrait
{
    /**
     * Method assumes that DeviceType and Device are already setup for $testScenario - to increase performance.
     *
     * @return array<DeviceType, Device, array>
     */
    public function executeDeviceFirmwareAuthenticationTest(AuthenticationMethod $deviceTypeAuthenticationMethod, AuthenticationMethod $requestAuthenticationMethod, array $credentials): void
    {
        $url = $this->getAndCreateFirmwareUrl();

        $this->sendDownloadFirmwareAuthenticationRequest(
            $url,
            $requestAuthenticationMethod,
        );

        $expectedResponseStatus = $this->getExpectedRequestResponseStatusForFirmwareDownloadSecurity(
            $deviceTypeAuthenticationMethod,
            $requestAuthenticationMethod,
            $credentials
        );

        $this->assertResponseCode($expectedResponseStatus);
    }

    public function sendDownloadFirmwareAuthenticationRequest(string $url, AuthenticationMethod $authenticationMethod)
    {
        switch ($authenticationMethod) {
            case AuthenticationMethod::X509:
                $sslCertificate = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'sslCertificate');
                $sslKey = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'sslKey');
                $this->loginX509Auth($sslCertificate, $sslKey);
                break;
            case AuthenticationMethod::BASIC:
                $username = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'username');
                $password = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'password');
                $this->loginBasicAuth($username, $password);
                break;
            case AuthenticationMethod::DIGEST:
                $username = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'username');
                $password = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'password');
                $this->loginDigestAuth($username, $password);
                break;
            case AuthenticationMethod::NONE:
                $this->clearAuthorization();
                break;
            default:
                throw new \Exception('Invalid authentication method "'.$authenticationMethod->value.'"');
        }

        $this->get('/device/check/auth/firmware', [], [], ['HTTP_X-Original-URI' => $url]);
    }

    /**
     * Method creates Firmware object for Device (doesn't setup template etc.), and then generates download firmware url.
     * Create Firmware object for Device and generate download firmware URL.
     *
     * Does not set up template.
     */
    public function getAndCreateFirmwareUrl(): string
    {
        $device = $this->getProvidedDeviceEntity();
        $deviceType = $this->getProvidedDeviceTypeEntity();

        // no point of randomizing if only one testcase is in "database" at a time
        $uuid = 'd0f73d'.$this->getRandomString(8);
        $secret = $this->getRandomString(6);

        $firmware = new Firmware();
        $firmware->setDeviceType($deviceType);
        $firmware->setFeature(Feature::PRIMARY);
        $firmware->setSourceType(SourceType::UPLOAD);
        $firmware->setName('TestFirmware');
        $firmware->setMd5(md5('TestFirmware'));
        $firmware->setFilename('firmware.zip');
        $firmware->setFilepath('/firmware.zip');
        $firmware->setVersion('1.0');
        $firmware->setUuid($uuid);
        $firmware->setSecret($secret);

        $this->getEntityManager()->persist($deviceType);
        $this->getEntityManager()->persist($firmware);
        $this->getEntityManager()->flush();

        return '/df/'.$device->getHashIdentifier().'/'.$firmware->getSecret().'/'.$firmware->getUploadDirPart().'/'.$firmware->getFilename();
    }
}
