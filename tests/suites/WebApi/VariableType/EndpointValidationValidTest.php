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

namespace Tests\Suites\WebApi\VariableType;

use App\DataFixtures as ProdFixtures;
use App\Entity\DeviceType;
use App\Entity\DeviceVariable;
use App\Entity\ImportFile;
use App\Entity\ImportFileRow;
use App\Entity\ImportFileRowVariable;
use App\Entity\TemplateVersionVariable;
use App\Enum\ImportFileRowImportStatus;
use App\Enum\ImportFileRowParseStatus;
use App\Enum\ImportFileStatus;
use App\Enum\VariableType;
use Carve\ApiBundle\Helper\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Utilities\Abstract\AbstractTestCase;

/**
 * Tests valid values against variable types and ensures that they are accepted by the API.
 *
 * Tests following endpoints:
 * - Device create and update
 * - Template version create and update
 * - Batch device variable add
 * - Batch import file row variable add
 */
#[Group('full')]
#[Group('smoke')]
#[Group('WebApi')] // To be used in CI parallel tests
class EndpointValidationValidTest extends AbstractTestCase
{
    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\ConfigurationFixtures::class,
            ProdFixtures\UserFixtures::class,
            ProdFixtures\DeviceTypeFixtures::class,
        ];
    }

    public static function getRow(VariableType $type, string $createValue, string $updateValue): array
    {
        return [$type, $createValue, $updateValue];
    }

    public static function getData(): array
    {
        $data = [];

        $data[] = self::getRow(
            type: VariableType::BOOLEAN,
            createValue: 'true',
            updateValue: 'false',
        );
        $data[] = self::getRow(
            type: VariableType::BOOLEAN,
            createValue: 'false',
            updateValue: 'true',
        );
        $data[] = self::getRow(
            type: VariableType::STRING,
            createValue: 'example1',
            updateValue: 'example2',
        );
        $data[] = self::getRow(
            type: VariableType::STRING,
            createValue: 'example',
            updateValue: ' ',
        );
        $data[] = self::getRow(
            type: VariableType::STRING,
            createValue: ' ',
            updateValue: 'example',
        );
        $data[] = self::getRow(
            type: VariableType::INTEGER,
            createValue: '123',
            updateValue: '456',
        );
        $data[] = self::getRow(
            type: VariableType::FLOAT,
            createValue: '123.45',
            updateValue: '678.9',
        );
        $data[] = self::getRow(
            type: VariableType::JSON_OBJECT,
            createValue: '{"key1":"value1"}',
            updateValue: '{"key2":"value2"}',
        );

        return $data;
    }

    public static function dataProvider(): array
    {
        return static::getProviderNamedData(static::getData(), static::getDataName(...));
    }

    public static function getDataName($row, $key): string
    {
        return 'Variable type "'.$row[0]->value.'" with create value "'.$row[1].'" and update value "'.$row[2].'"';
    }

    #[DataProvider('dataProvider')]
    public function testDevice(VariableType $type, string $createValue, string $updateValue): void
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK800']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        $variableName = 'variable';

        // Create
        $this->getApiClient()->request(
            uri: '/web/api/device/create',
            parameters: [
                'variables' => [
                    [
                        'name' => $variableName,
                        'variableValue' => $createValue,
                        'variableType' => $type->value,
                    ],
                ],
            ],
            asserts: [
                'variables.0.variableValue' => $createValue,
                'variables.0.variableType' => $type->value,
            ],
        );
        $variable = $this->getDeviceVariable($variableName, $this->getResponseId());
        $this->assertVariable($variable, $type, $createValue);

        // Update
        $this->getApiClient()->request(
            uri: '/web/api/device/{id}',
            method: 'POST',
            parameters: [
                'variables' => [
                    [
                        'name' => $variableName,
                        'variableValue' => $updateValue,
                        'variableType' => $type->value,
                    ],
                ],
            ],
            asserts: [
                'variables.0.variableValue' => $updateValue,
                'variables.0.variableType' => $type->value,
            ],
        );
        $variable = $this->getDeviceVariable($variableName, $this->getResponseId());
        $this->assertVariable($variable, $type, $updateValue);
    }

    #[DataProvider('dataProvider')]
    public function testTemplateVersion(VariableType $type, string $createValue, string $updateValue): void
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK800']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        $variableName = 'variable';

        $this->getApiClient()->request(
            uri: '/web/api/template/create',
        );

        // Create
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/create/staging/{template}',
            parameters: [
                'variables' => [
                    [
                        'name' => $variableName,
                        'variableValue' => $createValue,
                        'variableType' => $type->value,
                    ],
                ],
            ],
            asserts: [
                'variables.0.variableValue' => $createValue,
                'variables.0.variableType' => $type->value,
            ],
        );
        $variable = $this->getTemplateVersionVariable($variableName, $this->getResponseId());
        $this->assertVariable($variable, $type, $createValue);

        // Update
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/{id}',
            method: 'POST',
            parameters: [
                'variables' => [
                    [
                        'name' => $variableName,
                        'variableValue' => $updateValue,
                        'variableType' => $type->value,
                    ],
                ],
            ],
            asserts: [
                'variables.0.variableValue' => $updateValue,
                'variables.0.variableType' => $type->value,
            ],
        );
        $variable = $this->getTemplateVersionVariable($variableName, $this->getResponseId());
        $this->assertVariable($variable, $type, $updateValue);
    }

    #[DataProvider('dataProvider')]
    public function testBatchDevice(VariableType $type, string $createValue, string $updateValue): void
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK800']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        $variableName = 'variable';

        $this->getApiClient()->request(
            uri: '/web/api/device/create',
        );

        $id = $this->getResponseId();

        // Create
        $this->getApiClient()->request(
            uri: '/web/api/device/batch/variable/add',
            parameters: [
                'ids' => [
                    $id,
                ],
                'name' => $variableName,
                'variableValue' => $createValue,
                'variableType' => $type->value,
            ],
            // Disable apply provide on parameters for JSON_OBJECT type as it conflicts with JSON data in variableValue
            disableApplyProvideOnParameters: VariableType::JSON_OBJECT === $type,
        );
        $variable = $this->getDeviceVariable($variableName, $id);
        $this->assertVariable($variable, $type, $createValue);

        // Update
        $this->getApiClient()->request(
            uri: '/web/api/device/batch/variable/add',
            parameters: [
                'ids' => [
                    $id,
                ],
                'name' => $variableName,
                'variableValue' => $updateValue,
                'variableType' => $type->value,
            ],
            // Disable apply provide on parameters for JSON_OBJECT type as it conflicts with JSON data in variableValue
            disableApplyProvideOnParameters: VariableType::JSON_OBJECT === $type,
        );
        $variable = $this->getDeviceVariable($variableName, $id);
        $this->assertVariable($variable, $type, $updateValue);
    }

    #[DataProvider('dataProvider')]
    public function testBatchImportFileRow(VariableType $type, string $createValue, string $updateValue): void
    {
        $this->loginApi('admin', 'admin');

        $variableName = 'variable';

        // Create import file (can be invalid, but we need it to test batch endpoint)
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK800']);

        $importFile = new ImportFile();
        $importFile->setFilename('test.csv');
        $importFile->setFilepath('/test.csv');
        $importFile->setStatus(ImportFileStatus::UPLOADED);

        $importFileRow = new ImportFileRow();
        $importFileRow->setDeviceType($deviceType);
        $importFileRow->setRowKey(0);
        $importFileRow->setImportStatus(ImportFileRowImportStatus::PENDING);
        $importFileRow->setParseStatus(ImportFileRowParseStatus::VALID);
        $importFileRow->setImportFile($importFile);
        $importFile->getRows()->add($importFileRow);

        $this->getEntityManager()->persist($importFile);
        $this->getEntityManager()->persist($importFileRow);
        $this->getEntityManager()->flush();

        $id = $importFileRow->getId();
        $this->getEntityManager()->clear();

        // Create
        $this->getApiClient()->request(
            uri: '/web/api/importfilerow/batch/variable/add',
            parameters: [
                'ids' => [
                    $id,
                ],
                'name' => $variableName,
                'variableValue' => $createValue,
                'variableType' => $type->value,
            ],
            // Disable apply provide on parameters for JSON_OBJECT type as it conflicts with JSON data in variableValue
            disableApplyProvideOnParameters: VariableType::JSON_OBJECT === $type,
        );
        $variable = $this->getImportFileRowVariable($variableName, $id);
        $this->assertVariable($variable, $type, $createValue);

        // Update
        $this->getApiClient()->request(
            uri: '/web/api/importfilerow/batch/variable/add',
            parameters: [
                'ids' => [
                    $id,
                ],
                'name' => $variableName,
                'variableValue' => $updateValue,
                'variableType' => $type->value,
            ],
            // Disable apply provide on parameters for JSON_OBJECT type as it conflicts with JSON data in variableValue
            disableApplyProvideOnParameters: VariableType::JSON_OBJECT === $type,
        );
        $variable = $this->getImportFileRowVariable($variableName, $id);
        $this->assertVariable($variable, $type, $updateValue);
    }

    protected function getDeviceVariable(string $name, int $id): DeviceVariable
    {
        $this->getEntityManager()->clear();

        $variable = $this->getRepository(DeviceVariable::class)->findOneBy(['device' => $id, 'name' => $name]);
        $this->assertInstanceOf(DeviceVariable::class, $variable);

        return $variable;
    }

    protected function getTemplateVersionVariable(string $name, int $id): TemplateVersionVariable
    {
        $this->getEntityManager()->clear();

        $variable = $this->getRepository(TemplateVersionVariable::class)->findOneBy(['templateVersion' => $id, 'name' => $name]);
        $this->assertInstanceOf(TemplateVersionVariable::class, $variable);

        return $variable;
    }

    protected function getImportFileRowVariable(string $name, int $id): ImportFileRowVariable
    {
        $this->getEntityManager()->clear();

        $variable = $this->getRepository(ImportFileRowVariable::class)->findOneBy(['row' => $id, 'name' => $name]);
        $this->assertInstanceOf(ImportFileRowVariable::class, $variable);

        return $variable;
    }

    protected function getResponseId(): int
    {
        $response = $this->getResponseContentAsArray();
        $id = Arr::get($response, 'id');

        return $id;
    }

    protected function assertVariable(DeviceVariable|TemplateVersionVariable|ImportFileRowVariable $variable, VariableType $type, string $value): void
    {
        $this->assertSame($type, $variable->getVariableType());
        $this->assertSame($value, $variable->getVariableValue());
    }
}
