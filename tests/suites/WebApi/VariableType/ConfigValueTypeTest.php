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
use App\Entity\Config;
use App\Entity\Device;
use App\Enum\ConfigGenerator;
use App\Enum\VariableType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;

/**
 * Tests valid values and ensures that they are typed correctly in generated config.
 */
#[Group('full')]
#[Group('smoke')]
#[Group('WebApi')] // To be used in CI parallel tests
class ConfigValueTypeTest extends AbstractTestCase
{
    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\ConfigurationFixtures::class,
            ProdFixtures\UserFixtures::class,
            TestFixtures\WebApi\VariableType\ConfigValueTypeTestFixtures::class,
        ];
    }

    public static function getRow(VariableType $type, string $value, string $twigComparedValue): array
    {
        return [$type, $value, $twigComparedValue];
    }

    public static function getData(): array
    {
        $data = [];

        $data[] = self::getRow(
            type: VariableType::BOOLEAN,
            value: 'true',
            twigComparedValue: 'true',
        );
        $data[] = self::getRow(
            type: VariableType::BOOLEAN,
            value: 'false',
            twigComparedValue: 'false',
        );
        $data[] = self::getRow(
            type: VariableType::STRING,
            value: 'false',
            twigComparedValue: '"false"',
        );
        $data[] = self::getRow(
            type: VariableType::STRING,
            value: 'example',
            twigComparedValue: '"example"',
        );
        $data[] = self::getRow(
            type: VariableType::STRING,
            value: '{',
            twigComparedValue: '"{"',
        );
        $data[] = self::getRow(
            type: VariableType::INTEGER,
            value: '0',
            twigComparedValue: '0',
        );
        $data[] = self::getRow(
            type: VariableType::INTEGER,
            value: '123',
            twigComparedValue: '123',
        );
        $data[] = self::getRow(
            type: VariableType::INTEGER,
            value: '-4567',
            twigComparedValue: '-4567',
        );
        $data[] = self::getRow(
            type: VariableType::FLOAT,
            value: '0.0',
            twigComparedValue: '0.0',
        );
        $data[] = self::getRow(
            type: VariableType::FLOAT,
            value: '123.45',
            twigComparedValue: '123.45',
        );
        $data[] = self::getRow(
            type: VariableType::FLOAT,
            value: '-67.89',
            twigComparedValue: '-67.89',
        );
        $data[] = self::getRow(
            type: VariableType::JSON_OBJECT,
            value: '{}',
            twigComparedValue: '[]',
        );
        $data[] = self::getRow(
            type: VariableType::JSON_OBJECT,
            value: '{"key1":"value1"}',
            twigComparedValue: '{"key1":"value1"}',
        );

        return $data;
    }

    public static function dataProvider(): array
    {
        return static::getProviderNamedData(static::getData(), static::getDataName(...));
    }

    public static function getDataName($row, $key): string
    {
        return 'Variable type "'.$row[0]->value.'" with value "'.$row[1].'" and compared value "'.$row[2].'"';
    }

    #[DataProvider('dataProvider')]
    public function testConfig(VariableType $type, string $value, string $comparedValue): void
    {
        $this->loginApi('admin', 'admin');

        $device = $this->getRepository(Device::class)->findOneBy([]);
        $config = $this->getRepository(Config::class)->findOneBy([]);
        $this->getApiClient()->provide(Device::class, ['id' => $device->getId()]);
        $this->getApiClient()->provide(Config::class, ['id' => $config->getId()]);

        $variableName = 'variable';

        $content = '{% if '.$variableName.' is same as('.$comparedValue.') %}TRUE{% else %}FALSE{% endif %}';
        $expectedGeneratedConfig = '"TRUE"';

        $this->getApiClient()->request(
            uri: '/web/api/device/batch/variable/add',
            parameters: [
                'ids' => [
                    $device->getId(),
                ],
                'name' => $variableName,
                'variableValue' => $value,
                'variableType' => $type->value,
            ],
            disableApplyProvideOnParameters: true,
        );
        $this->getApiClient()->request(
            uri: '/web/api/config/{id}',
            method: 'POST',
            parameters: [
                'name' => 'Config 1',
                'generator' => ConfigGenerator::TWIG,
                'content' => $content,
            ],
            disableApplyProvideOnParameters: true,
        );

        $this->getApiClient()->request(
            uri: '/web/api/device/{id}/generate/config/primary',
        );

        $this->assertSame($expectedGeneratedConfig, $this->getResponseContent(), 'Generated config content does not match expected config content');
    }
}
