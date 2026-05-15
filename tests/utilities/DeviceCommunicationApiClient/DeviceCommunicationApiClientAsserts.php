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

namespace Tests\Utilities\DeviceCommunicationApiClient;

use Carve\ApiBundle\Helper\Arr;
use Tests\Utilities\ApiClient\ApiClientRequest;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceModel;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceTypeModel;

// TODO add docs what is expected to be provided for each assert to work correctly
trait DeviceCommunicationApiClientAsserts
{
    public function assertDeviceCommunication(mixed $assert, mixed $parameterName, array $parameters, array $uriParameters, array $variables, ApiClientRequest $request)
    {
        if ($assert instanceof DeviceCommunicationApiClientAssert) {
            $this->assertDeviceCommunicationEnum($assert, $parameterName, $parameters, $uriParameters, $variables, $request);
        } else {
            $this->assert($assert, $parameterName, $parameters, $uriParameters, $request);
        }
    }

    public function assertDeviceCommunicationEnum(mixed $assert, mixed $parameterName, array $parameters, array $uriParameters, array $variables, ApiClientRequest $request): void
    {
        switch ($assert) {
            case DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS:
                $this->assertDeviceEntityUsingTestCaseClass(
                    methodName: 'assertDeviceEntityExists',
                    assert: $assert,
                    parameters: $parameters,
                    request: $request
                );
                break;
            case DeviceCommunicationApiClientAssert::DEVICE_ENTITY_NOT_EXISTS:
                $this->assertDeviceEntityUsingTestCaseClass(
                    methodName: 'assertDeviceEntityNotExists',
                    assert: $assert,
                    parameters: $parameters,
                    request: $request
                );
                break;
            case DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_AND_DISABLED:
                $this->assertDeviceEntityUsingTestCaseClass(
                    methodName: 'assertDeviceEntityExistsAndDisabled',
                    assert: $assert,
                    parameters: $parameters,
                    request: $request
                );
                break;
            case DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_AND_ENABLED:
                $this->assertDeviceEntityUsingTestCaseClass(
                    methodName: 'assertDeviceEntityExistsAndEnabled',
                    assert: $assert,
                    parameters: $parameters,
                    request: $request
                );
                break;
            case DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_WITHOUT_DEVICE_SECRETS:
                $this->assertDeviceEntityStateUsingTestCaseClass(
                    methodName: 'assertDeviceWithoutDeviceSecrets',
                    assert: $assert,
                    parameters: $parameters,
                    request: $request
                );
                break;
            case DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_WITHOUT_DEVICE_CERTIFICATES:
                $this->assertDeviceEntityStateUsingTestCaseClass(
                    methodName: 'assertDeviceWithoutDeviceCertificates',
                    assert: $assert,
                    parameters: $parameters,
                    request: $request
                );
                break;
            case DeviceCommunicationApiClientAssert::DEVICE_CONNECTIONS_AMOUNT_COUNT:
                $this->assertDeviceConnectionsAmountCount(
                    assert: $assert,
                    variables: $variables,
                    request: $request
                );
                break;
            case DeviceCommunicationApiClientAssert::RESPONSE_VALUE_EQUALS_VARIABLE_VALUE:
                $this->assertResponseValueEqualsVariableValue(
                    assert: $assert,
                    parameterName: $parameterName,
                    variables: $variables,
                    request: $request
                );
                break;
            case DeviceCommunicationApiClientAssert::RESPONSE_VALUE_IS_NEW_DEVICE_IDENTIFIER:
                $this->assertResponseValueIsNewDeviceIdentifier(
                    assert: $assert,
                    parameterName: $parameterName,
                    request: $request
                );
                break;

            case DeviceCommunicationApiClientAssert::FIRMWARE_URL_IN_RESPONSE:
                $this->assertFirmwareUrlInResponse(
                    assert: $assert,
                    variables: $variables,
                    request: $request
                );
                break;
            default:
                throw new \Exception('Unknown assert: '.$assert->value);
        }
    }

    public function assertFirmwareUrlInResponse(mixed $assert, array $variables, ApiClientRequest $request): void
    {
        $device = $this->getRequiredProvidedEntityParameter(DeviceModel::class, 'device');
        $parameterName = 'firmwareUrlExtractionFunction';
        $expectedFirmwareUrlParameterName = 'expectedFirmwareUrl';

        if (!Arr::has($variables, $expectedFirmwareUrlParameterName)) {
            $this->throw('Value for key "'.$expectedFirmwareUrlParameterName.'" not found in the $variables', __FUNCTION__, $request);
        }

        $expectedFirmwareUrl = Arr::get($variables, $expectedFirmwareUrlParameterName);

        if (!Arr::has($variables, $parameterName)) {
            $this->throw('Value for key "'.$parameterName.'" not found in the $variables', __FUNCTION__, $request);
        }

        $firmwareUrlExtractionFunction = Arr::get($variables, $parameterName);
        $responseContent = $this->testCase->getResponseContent();

        $extractedFirmwareUrl = $firmwareUrlExtractionFunction($responseContent);

        $this->testCase->assertSame($expectedFirmwareUrl, $extractedFirmwareUrl, 'Value for key "'.$expectedFirmwareUrlParameterName.'" in the response is different then one in $variables. Expected "'.(string) $expectedFirmwareUrl.'" but got "'.(string) $extractedFirmwareUrl.'". '.$this->getAssertMessageSuffix(__FUNCTION__, $request));
    }

    public function assertDeviceEntityStateUsingTestCaseClass(string $methodName, mixed $assert, array $parameters, ApiClientRequest $request): void
    {
        $device = $this->getRequiredProvidedEntityParameter(DeviceModel::class, 'device');

        if (!method_exists($this->testCase, $methodName)) {
            $this->throw('Method "'.$methodName.'" does not exist in test case class', __FUNCTION__, $request);
        }

        $this->testCase->$methodName(
            $device,
           $this->getAssertMessageSuffix(__FUNCTION__, $request)
        );
    }

    public function assertDeviceConnectionsAmountCount(mixed $assert, array $variables, ApiClientRequest $request): void
    {
        $device = $this->getRequiredProvidedEntityParameter(DeviceModel::class, 'device');

        $expectedDeviceConnectionAmount = Arr::get($variables, 'expectedDeviceConnectionAmount');
        if (null === $expectedDeviceConnectionAmount) {
            $this->throw('ExpectedDeviceConnectionAmount is not set in the request variables', __FUNCTION__, $request);
        }

        $this->testCase->assertDeviceConnectionsAmountCount(
           $device,
           $expectedDeviceConnectionAmount,
           $this->getAssertMessageSuffix(__FUNCTION__, $request)
        );
    }

    public function assertResponseValueEqualsVariableValue(mixed $assert, mixed $parameterName, array $variables, ApiClientRequest $request): void
    {
        $response = $this->testCase->getResponseContentAsArray();
        if (!Arr::has($response, $parameterName)) {
            $this->throw('Value for key "'.$parameterName.'" not found in the response', __FUNCTION__, $request);
        }

        if (!Arr::has($variables, $parameterName)) {
            $this->throw('Value for key "'.$parameterName.'" not found in the $variables', __FUNCTION__, $request);
        }

        $actual = Arr::get($response, $parameterName);
        $expected = Arr::get($variables, $parameterName);
        $this->testCase->assertSame($expected, $actual, 'Value for key "'.$parameterName.'" in the response is different then one in $variables. Expected "'.(string) $expected.'" but got "'.(string) $actual.'". '.$this->getAssertMessageSuffix(__FUNCTION__, $request));
    }

    public function assertResponseValueIsNewDeviceIdentifier(mixed $assert, mixed $parameterName, ApiClientRequest $request): void
    {
        $response = $this->testCase->getResponseContentAsArray();
        if (!Arr::has($response, $parameterName)) {
            $this->throw('Value for key "'.$parameterName.'" not found in the response', __FUNCTION__, $request);
        }

        $newDeviceIdentifier = Arr::get($response, $parameterName);
        $deviceTypeProvidedEntity = $this->getProvidedEntityClass(DeviceTypeModel::class, true);
        $deviceTypeProvidedEntity['deviceIdentifier'] = $newDeviceIdentifier;

        $this->provide(DeviceTypeModel::class, $deviceTypeProvidedEntity);
    }

    public function assertDeviceEntityUsingTestCaseClass(string $methodName, mixed $assert, array $parameters, ApiClientRequest $request): void
    {
        $deviceType = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'deviceType');

        $deviceIdentifier = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'deviceIdentifier');

        if (!method_exists($this->testCase, $methodName)) {
            $this->throw('Method "'.$methodName.'" does not exist in test case class', __FUNCTION__, $request);
        }

        $device = $this->testCase->$methodName(
            $deviceType,
            $deviceIdentifier,
           $this->getAssertMessageSuffix(__FUNCTION__, $request)
        );

        $this->provide(
            DeviceModel::class,
            [
                'id' => $device->getId(),
                'device' => $device,
            ]
        );
    }
}
