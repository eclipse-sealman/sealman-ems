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

namespace Tests\Suites\Unit\VpnAddressManager;

use App\DataFixtures as ProdFixtures;
use App\Entity\Configuration;
use App\Entity\VpnSubnet;
use App\Enum\VpnSubnetType;
use App\Service\VpnAddressManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;

/**
 * Testing VpnAddressManager::processConfiguration method.
 */
#[Group('Unit')] // To be used in CI parallel tests
class VpnAddressManagerProcessConfigurationTest extends AbstractTestCase
{
    // Provider array keys
    public const DEV__VPN_IP__SUBNETS = 'deviceVpnIpSubnets';
    public const DEV__VPN_IP__RANGES_ = 'deviceVpnIpRanges';
    public const TECH_VPN_IP__SUBNETS = 'techVpnIpSubnets';
    public const TECH_VPN_IP__RANGES_ = 'techVpnIpRanges';
    public const DEV__VIRTUAL_SUBNETS = 'deviceVirtualSubnets';
    public const DEV__VIRTUAL_RANGES_ = 'deviceVirtualRanges';

    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\CertificateTypeFixtures::class,
            ProdFixtures\ConfigurationFixtures::class,
            ProdFixtures\DeviceTypeFixtures::class,
            ProdFixtures\UserDeviceSecretCredentialsFixtures::class,
            ProdFixtures\UserDeviceX509CredentialsFixtures::class,
            ProdFixtures\UserFixtures::class,
            TestFixtures\Configuration\ScepFixtures::class,
            TestFixtures\Configuration\VpnFixtures::class,
            TestFixtures\Device\DeviceFixtures::class,
            TestFixtures\User\AdminFixtures::class,
        ];
    }

    public static function vpnConfigurationChangeTestsProvider(): array
    {
        /*
        Provider format:
        - array describing previous state of VPN configuration and VPN subnets - using consts
        - array describing new state of VPN configuration and VPN subnets - using consts
        - expected to be valid boolean - if configuration change form validation valid (on true) or returns errors (on false)
        */
        $result = [];

        $result['t1'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.0.1.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.0.1.1-10.0.1.2',
                static::TECH_VPN_IP__SUBNETS => '192.168.0.0/23',
                static::TECH_VPN_IP__RANGES_ => '192.168.0.1-192.168.1.254',
                static::DEV__VIRTUAL_SUBNETS => '10.2.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.2.0.1-10.2.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '192.168.0.0/23',
                static::DEV__VPN_IP__RANGES_ => '192.168.0.1-192.168.1.254',
                static::TECH_VPN_IP__SUBNETS => '172.16.0.0/16',
                static::TECH_VPN_IP__RANGES_ => '172.16.0.1-172.16.255.254',
                static::DEV__VIRTUAL_SUBNETS => '10.2.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.2.0.1-10.2.0.255',
            ],
            true,
        ];

        $result['t2'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.1.0.0/17',
                static::DEV__VPN_IP__RANGES_ => '10.1.0.0-10.1.127.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.5.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.5.1-192.168.5.254',
                static::DEV__VIRTUAL_SUBNETS => '10.1.128.0/17',
                static::DEV__VIRTUAL_RANGES_ => '10.1.128.0-10.1.255.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '192.168.6.0/24',
                static::DEV__VPN_IP__RANGES_ => '192.168.6.1-192.168.6.254',
                static::TECH_VPN_IP__SUBNETS => '192.168.5.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.5.1-192.168.5.254',
                static::DEV__VIRTUAL_SUBNETS => '10.1.0.0/16',
                static::DEV__VIRTUAL_RANGES_ => '10.1.0.0-10.1.255.255',
            ],
            true,
        ];

        $result['t3'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.1-10.3.0.255',
                static::TECH_VPN_IP__SUBNETS => '172.16.0.0/16',
                static::TECH_VPN_IP__RANGES_ => '172.16.0.1-172.16.255.254',
                static::DEV__VIRTUAL_SUBNETS => '10.4.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.4.0.1-10.4.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.1-10.3.0.255',
                static::TECH_VPN_IP__SUBNETS => '172.16.0.0/17',
                static::TECH_VPN_IP__RANGES_ => '172.16.0.1-172.16.127.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.128.0/17',
                static::DEV__VIRTUAL_RANGES_ => '172.16.128.0-172.16.255.255',
            ],
            true,
        ];

        $result['t4'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.0.1.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.0.1.1-10.0.1.2',
                static::TECH_VPN_IP__SUBNETS => '192.168.10.0/23',
                static::TECH_VPN_IP__RANGES_ => '192.168.10.1-192.168.11.254',
                static::DEV__VIRTUAL_SUBNETS => '192.168.12.0/23',
                static::DEV__VIRTUAL_RANGES_ => '192.168.12.1-192.168.13.254',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.0.1.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.0.1.1-10.0.1.2',
                static::TECH_VPN_IP__SUBNETS => '192.168.10.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.10.1-192.168.10.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.11.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.11.1-192.168.11.255',
            ],
            true,
        ];

        $result['t5'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.2.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.2.0.0-10.2.255.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.10.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.10.1-192.168.10.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.20.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.20.1-192.168.20.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.2.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.2.0.0-10.2.255.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.10.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.10.1-192.168.10.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.10.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.10.1-192.168.10.255',
            ],
            false,
        ];

        $result['t6'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.0-10.3.255.255',
                static::TECH_VPN_IP__SUBNETS => '10.3.128.0/17',
                static::TECH_VPN_IP__RANGES_ => '10.3.128.0-10.3.255.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.0.1-172.16.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.0-10.3.127.255',
                static::TECH_VPN_IP__SUBNETS => '10.3.128.0/17',
                static::TECH_VPN_IP__RANGES_ => '10.3.128.0-10.3.255.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.0.1-172.16.0.255',
            ],
            false,
        ];

        $result['t7'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.0-10.3.255.255',
                static::TECH_VPN_IP__SUBNETS => '10.4.128.0/17',
                static::TECH_VPN_IP__RANGES_ => '10.4.128.0-10.4.255.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.0.1-172.16.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.0-10.3.127.255',
                static::TECH_VPN_IP__SUBNETS => '10.4.128.0/17',
                static::TECH_VPN_IP__RANGES_ => '10.4.128.0-10.4.255.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.0.1-172.16.0.255',
            ],
            true,
        ];

        $result['t8'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.0-10.3.0.255',
                static::TECH_VPN_IP__SUBNETS => '10.4.128.0/17',
                static::TECH_VPN_IP__RANGES_ => '10.4.128.0-10.4.255.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.0.1-172.16.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.0-10.3.0.127',
                static::TECH_VPN_IP__SUBNETS => '10.4.128.0/17',
                static::TECH_VPN_IP__RANGES_ => '10.4.128.0-10.4.255.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.0.1-172.16.0.255',
            ],
            true,
        ];

        $result['t9'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.5.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.5.0.0-10.5.255.255',
                static::TECH_VPN_IP__SUBNETS => '172.18.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '172.18.0.1-172.18.0.255',
                static::DEV__VIRTUAL_SUBNETS => '10.6.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.6.0.1-10.6.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.5.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.5.0.0-10.5.255.255',
                static::TECH_VPN_IP__SUBNETS => '172.18.0.0/25',
                static::TECH_VPN_IP__RANGES_ => '172.18.0.1-172.18.0.127',
                static::DEV__VIRTUAL_SUBNETS => '172.18.0.128/25',
                static::DEV__VIRTUAL_RANGES_ => '172.18.0.128-172.18.0.255',
            ],
            true,
        ];

        $result['t10'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.4.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.4.0.0-10.4.255.255',
                static::TECH_VPN_IP__SUBNETS => '10.7.0.0/16',
                static::TECH_VPN_IP__RANGES_ => '10.7.0.1-10.7.255.255',
                static::DEV__VIRTUAL_SUBNETS => '10.8.0.0/16',
                static::DEV__VIRTUAL_RANGES_ => '10.8.0.0-10.8.255.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.4.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.4.0.0-10.4.255.255',
                static::TECH_VPN_IP__SUBNETS => '10.7.0.0/16',
                static::TECH_VPN_IP__RANGES_ => '10.7.0.1-10.7.255.255',
                static::DEV__VIRTUAL_SUBNETS => '10.7.0.0/16',
                static::DEV__VIRTUAL_RANGES_ => '10.7.0.0-10.7.255.255',
            ],
            false,
        ];

        $result['t11'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.10.0.0/17',
                static::DEV__VPN_IP__RANGES_ => '10.10.0.0-10.10.127.255',
                static::TECH_VPN_IP__SUBNETS => '10.9.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.9.0.1-10.9.0.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.10.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.10.1-172.16.10.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.10.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.10.0.0-10.10.255.255',
                static::TECH_VPN_IP__SUBNETS => '10.9.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.9.0.1-10.9.0.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.10.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.10.1-172.16.10.255',
            ],
            true,
        ];

        $result['t12'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.10.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.10.0.0-10.10.255.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.5.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.5.1-192.168.5.255',
                static::DEV__VIRTUAL_SUBNETS => '172.20.10.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.20.10.1-172.20.10.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.10.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.10.0.0-10.10.255.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.5.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.5.1-192.168.5.255',
                static::DEV__VIRTUAL_SUBNETS => '172.20.10.0/25',
                static::DEV__VIRTUAL_RANGES_ => '172.20.10.1-172.20.10.127',
            ],
            true,
        ];

        $result['t13'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.5.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.5.0.0-10.5.0.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.20.0/25',
                static::TECH_VPN_IP__RANGES_ => '192.168.20.1-192.168.20.127',
                static::DEV__VIRTUAL_SUBNETS => '10.6.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.6.0.1-10.6.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.5.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.5.0.0-10.5.0.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.20.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.20.1-192.168.20.255',
                static::DEV__VIRTUAL_SUBNETS => '10.6.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.6.0.1-10.6.0.255',
            ],
            true,
        ];

        $result['t14'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '172.18.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '172.18.0.0-172.18.255.255',
                static::TECH_VPN_IP__SUBNETS => '10.2.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.2.0.1-10.2.0.255',
                static::DEV__VIRTUAL_SUBNETS => '10.4.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.4.0.1-10.4.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '172.18.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '172.18.0.0-172.18.255.255',
                static::TECH_VPN_IP__SUBNETS => '10.2.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.2.0.1-10.2.0.255',
                static::DEV__VIRTUAL_SUBNETS => '10.4.0.0/25',
                static::DEV__VIRTUAL_RANGES_ => '10.4.0.1-10.4.0.127',
            ],
            true,
        ];

        $result['t15'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.0-10.3.255.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.0.1-192.168.0.255',
                static::DEV__VIRTUAL_SUBNETS => '10.4.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.4.0.1-10.4.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.0-10.3.255.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.0.0/25',
                static::TECH_VPN_IP__RANGES_ => '192.168.0.1-192.168.0.127',
                static::DEV__VIRTUAL_SUBNETS => '192.168.0.128/25',
                static::DEV__VIRTUAL_RANGES_ => '192.168.0.128-192.168.0.255',
            ],
            true,
        ];

        $result['t16'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.0.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.0.0.0-10.0.255.255',
                static::TECH_VPN_IP__SUBNETS => '10.1.0.0/16',
                static::TECH_VPN_IP__RANGES_ => '10.1.0.1-10.1.255.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.0.1-172.16.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.0.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.0.0.0-10.0.255.255',
                static::TECH_VPN_IP__SUBNETS => '10.1.0.0/16',
                static::TECH_VPN_IP__RANGES_ => '10.1.0.1-10.1.255.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.0.1-172.16.0.255',
            ],
            true,
        ];

        $result['t17'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.0.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.0.0.1-10.0.0.255',
                static::TECH_VPN_IP__SUBNETS => '10.1.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.1.0.1-10.1.0.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.0.1-172.16.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.0.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.0.0.1-10.0.0.255',
                static::TECH_VPN_IP__SUBNETS => '10.1.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.1.0.1-10.1.0.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.0.1-172.16.0.255',
            ],
            true,
        ];

        $result['t18'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.10.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.10.0.1-10.10.0.255',
                static::TECH_VPN_IP__SUBNETS => '172.20.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '172.20.0.1-172.20.0.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.0.1-192.168.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.10.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.10.0.1-10.10.0.255',
                static::TECH_VPN_IP__SUBNETS => '172.20.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '172.20.0.1-172.20.0.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.0.1-192.168.0.255',
            ],
            true,
        ];

        $result['t19'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.1.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.1.0.1-10.1.0.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.10.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.10.1-192.168.10.255',
                static::DEV__VIRTUAL_SUBNETS => '172.30.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.30.0.1-172.30.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.1.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.1.0.1-10.1.0.127',
                static::TECH_VPN_IP__SUBNETS => '192.168.10.0/25',
                static::TECH_VPN_IP__RANGES_ => '192.168.10.1-192.168.10.127',
                static::DEV__VIRTUAL_SUBNETS => '192.168.10.128/25',
                static::DEV__VIRTUAL_RANGES_ => '192.168.10.128-192.168.10.255',
            ],
            true,
        ];

        $result['t20'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '172.30.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '172.30.0.0-172.30.255.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.10.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.10.1-192.168.10.255',
                static::DEV__VIRTUAL_SUBNETS => '10.5.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.5.0.1-10.5.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '172.30.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '172.30.0.1-172.30.0.127',
                static::TECH_VPN_IP__SUBNETS => '192.168.10.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.10.1-192.168.10.255',
                static::DEV__VIRTUAL_SUBNETS => '10.5.0.128/25',
                static::DEV__VIRTUAL_RANGES_ => '10.5.0.129-10.5.0.255',
            ],
            true,
        ];

        $result['t21'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.2.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.2.0.0-10.2.255.255',
                static::TECH_VPN_IP__SUBNETS => '172.16.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '172.16.0.1-172.16.0.255',
                static::DEV__VIRTUAL_SUBNETS => '10.4.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.4.0.1-10.4.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.2.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.2.0.0-10.2.255.255',
                static::TECH_VPN_IP__SUBNETS => '172.16.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '172.16.0.1-172.16.0.255',
                static::DEV__VIRTUAL_SUBNETS => '10.4.0.0/25',
                static::DEV__VIRTUAL_RANGES_ => '10.4.0.1-10.4.0.127',
            ],
            true,
        ];

        $result['t22'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '172.18.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '172.18.0.0-172.18.255.255',
                static::TECH_VPN_IP__SUBNETS => '10.6.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.6.0.1-10.6.0.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.1.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.1.1-192.168.1.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '172.18.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '172.18.0.0-172.18.255.255',
                static::TECH_VPN_IP__SUBNETS => '10.6.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.6.0.1-10.6.0.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.1.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.1.1-192.168.1.255',
            ],
            true,
        ];

        $result['t23'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.0-10.3.255.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.2.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.2.1-192.168.2.255',
                static::DEV__VIRTUAL_SUBNETS => '172.20.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.20.0.1-172.20.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.0-10.3.255.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.2.0/25',
                static::TECH_VPN_IP__RANGES_ => '192.168.2.1-192.168.2.127',
                static::DEV__VIRTUAL_SUBNETS => '192.168.2.128/25',
                static::DEV__VIRTUAL_RANGES_ => '192.168.2.128-192.168.2.255',
            ],
            true,
        ];

        $result['t24'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '172.16.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '172.16.0.0-172.16.127.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.1.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.1.1-192.168.1.255',
                static::DEV__VIRTUAL_SUBNETS => '10.2.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.2.0.1-10.2.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '172.16.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '172.16.0.0-172.16.255.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.1.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.1.1-192.168.1.255',
                static::DEV__VIRTUAL_SUBNETS => '10.2.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.2.0.1-10.2.0.255',
            ],
            true,
        ];

        $result['t25'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '172.16.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '172.16.0.0-172.16.255.255',
                static::TECH_VPN_IP__SUBNETS => '10.1.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.1.0.1-10.1.0.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.10.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.10.1-192.168.10.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '172.16.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '172.16.0.0-172.16.255.255',
                static::TECH_VPN_IP__SUBNETS => '10.1.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.1.0.1-10.1.0.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.10.0/25',
                static::DEV__VIRTUAL_RANGES_ => '192.168.10.1-192.168.10.127',
            ],
            true,
        ];

        $result['t26'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.5.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.5.0.1-10.5.0.255',
                static::TECH_VPN_IP__SUBNETS => '172.16.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '172.16.0.1-172.16.0.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.1.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.1.1-192.168.1.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.5.0.0/23',
                static::DEV__VPN_IP__RANGES_ => '10.5.0.1-10.5.1.255',
                static::TECH_VPN_IP__SUBNETS => '172.16.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '172.16.0.1-172.16.0.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.1.0/23',
                static::DEV__VIRTUAL_RANGES_ => '192.168.1.1-192.168.2.255',
            ],
            false,
        ];

        $result['t27'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.0-10.3.255.255',
                static::TECH_VPN_IP__SUBNETS => '172.20.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '172.20.0.1-172.20.0.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.0.1-192.168.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.0-10.3.255.255',
                static::TECH_VPN_IP__SUBNETS => '172.20.0.0/25',
                static::TECH_VPN_IP__RANGES_ => '172.20.0.1-172.20.0.127',
                static::DEV__VIRTUAL_SUBNETS => '172.20.0.128/25',
                static::DEV__VIRTUAL_RANGES_ => '172.20.0.128-172.20.0.255',
            ],
            true,
        ];

        $result['t28'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.5.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.5.0.1-10.5.0.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.3.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.3.1-192.168.3.255',
                static::DEV__VIRTUAL_SUBNETS => '172.20.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.20.0.1-172.20.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.5.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.5.0.1-10.5.0.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.3.0/25',
                static::TECH_VPN_IP__RANGES_ => '192.168.3.1-192.168.3.127',
                static::DEV__VIRTUAL_SUBNETS => '172.20.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.20.0.1-172.20.0.255',
            ],
            true,
        ];

        $result['t29'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '172.16.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '172.16.0.1-172.16.0.255',
                static::TECH_VPN_IP__SUBNETS => '10.1.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.1.0.1-10.1.0.255',
                static::DEV__VIRTUAL_SUBNETS => '10.4.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.4.0.1-10.4.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '172.16.0.0/25',
                static::DEV__VPN_IP__RANGES_ => '172.16.0.1-172.16.0.127',
                static::TECH_VPN_IP__SUBNETS => '10.1.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.1.0.1-10.1.0.255',
                static::DEV__VIRTUAL_SUBNETS => '10.4.0.0/25',
                static::DEV__VIRTUAL_RANGES_ => '10.4.0.1-10.4.0.127',
            ],
            true,
        ];

        $result['t30'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '192.168.1.0/24',
                static::DEV__VPN_IP__RANGES_ => '192.168.1.1-192.168.1.255',
                static::TECH_VPN_IP__SUBNETS => '10.0.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.0.0.1-10.0.0.255',
                static::DEV__VIRTUAL_SUBNETS => '10.3.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.3.0.1-10.3.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '192.168.0.0/23',
                static::DEV__VPN_IP__RANGES_ => '192.168.1.1-192.168.1.255',
                static::TECH_VPN_IP__SUBNETS => '10.0.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.0.0.1-10.0.0.255',
                static::DEV__VIRTUAL_SUBNETS => '10.3.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.3.0.1-10.3.0.255',
            ],
            true,
        ];

        $result['t31'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.5.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.5.0.1-10.5.0.255',
                static::TECH_VPN_IP__SUBNETS => '172.16.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '172.16.0.1-172.16.0.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.0.1-192.168.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.5.0.0/23',
                static::DEV__VPN_IP__RANGES_ => '10.5.0.1-10.5.1.255',
                static::TECH_VPN_IP__SUBNETS => '172.16.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '172.16.0.1-172.16.0.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.0.1-192.168.0.255',
            ],
            true,
        ];

        $result['t32'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '172.16.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '172.16.0.1-172.16.0.255',
                static::TECH_VPN_IP__SUBNETS => '10.1.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.1.0.1-10.1.0.255',
                static::DEV__VIRTUAL_SUBNETS => '10.2.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.2.0.1-10.2.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '172.16.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '172.16.0.1-172.16.0.255',
                static::TECH_VPN_IP__SUBNETS => '10.1.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.1.0.1-10.1.0.255',
                static::DEV__VIRTUAL_SUBNETS => '10.2.0.0/23',
                static::DEV__VIRTUAL_RANGES_ => '10.2.0.1-10.2.1.255',
            ],
            true,
        ];

        $result['t33'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '172.20.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '172.20.0.1-172.20.0.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.1.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.1.1-192.168.1.255',
                static::DEV__VIRTUAL_SUBNETS => '10.4.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.4.0.1-10.4.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '172.20.0.0/25',
                static::DEV__VPN_IP__RANGES_ => '172.20.0.1-172.20.0.127',
                static::TECH_VPN_IP__SUBNETS => '192.168.1.0/25',
                static::TECH_VPN_IP__RANGES_ => '192.168.1.1-192.168.1.127',
                static::DEV__VIRTUAL_SUBNETS => '10.4.0.128/25',
                static::DEV__VIRTUAL_RANGES_ => '10.4.0.129-10.4.0.255',
            ],
            true,
        ];

        $result['t34'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '192.168.2.0/24',
                static::DEV__VPN_IP__RANGES_ => '192.168.2.1-192.168.2.255',
                static::TECH_VPN_IP__SUBNETS => '172.16.5.0/24',
                static::TECH_VPN_IP__RANGES_ => '172.16.5.1-172.16.5.255',
                static::DEV__VIRTUAL_SUBNETS => '10.1.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.1.0.1-10.1.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '192.168.2.0/24',
                static::DEV__VPN_IP__RANGES_ => '192.168.2.1-192.168.2.255',
                static::TECH_VPN_IP__SUBNETS => '172.16.5.0/25',
                static::TECH_VPN_IP__RANGES_ => '172.16.5.1-172.16.5.127',
                static::DEV__VIRTUAL_SUBNETS => '10.1.0.128/25',
                static::DEV__VIRTUAL_RANGES_ => '10.1.0.129-10.1.0.255',
            ],
            true,
        ];

        $result['t35'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.0.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.0.0.1-10.0.0.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.0.1-192.168.0.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.0.1-172.16.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.0.0.0/25',
                static::DEV__VPN_IP__RANGES_ => '10.0.0.1-10.0.0.127',
                static::TECH_VPN_IP__SUBNETS => '192.168.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.0.1-192.168.0.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.0.1-172.16.0.255',
            ],
            true,
        ];

        $result['t36'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.1.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.1.0.1-10.1.0.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.1.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.1.1-192.168.1.255',
                static::DEV__VIRTUAL_SUBNETS => '172.20.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.20.0.1-172.20.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.1.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.1.0.1-10.1.0.255',
                static::TECH_VPN_IP__SUBNETS => '192.168.1.0/25',
                static::TECH_VPN_IP__RANGES_ => '192.168.1.1-192.168.1.127',
                static::DEV__VIRTUAL_SUBNETS => '172.20.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.20.0.1-172.20.0.255',
            ],
            true,
        ];

        $result['t37'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '192.168.2.0/24',
                static::DEV__VPN_IP__RANGES_ => '192.168.2.1-192.168.2.255',
                static::TECH_VPN_IP__SUBNETS => '10.2.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.2.0.1-10.2.0.255',
                static::DEV__VIRTUAL_SUBNETS => '10.3.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.3.0.1-10.3.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '192.168.2.0/24',
                static::DEV__VPN_IP__RANGES_ => '192.168.2.1-192.168.2.255',
                static::TECH_VPN_IP__SUBNETS => '10.2.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.2.0.1-10.2.0.255',
                static::DEV__VIRTUAL_SUBNETS => '10.3.0.0/23',
                static::DEV__VIRTUAL_RANGES_ => '10.3.0.1-10.3.1.255',
            ],
            true,
        ];

        $result['t38'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '172.16.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '172.16.0.1-172.16.0.255',
                static::TECH_VPN_IP__SUBNETS => '10.3.0.0/24',
                static::TECH_VPN_IP__RANGES_ => '10.3.0.1-10.3.0.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.3.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.3.1-192.168.3.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '172.16.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '172.16.0.1-172.16.0.255',
                static::TECH_VPN_IP__SUBNETS => '10.3.0.0/23',
                static::TECH_VPN_IP__RANGES_ => '10.3.0.1-10.3.1.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.3.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.3.1-192.168.3.255',
            ],
            true,
        ];

        $result['t39'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.4.0.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.4.0.1-10.4.0.255',
                static::TECH_VPN_IP__SUBNETS => '172.16.1.0/24',
                static::TECH_VPN_IP__RANGES_ => '172.16.1.1-172.16.1.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.4.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.4.1-192.168.4.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.4.0.0/25',
                static::DEV__VPN_IP__RANGES_ => '10.4.0.1-10.4.0.127',
                static::TECH_VPN_IP__SUBNETS => '172.16.1.0/24',
                static::TECH_VPN_IP__RANGES_ => '172.16.1.1-172.16.1.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.4.0/25',
                static::DEV__VIRTUAL_RANGES_ => '192.168.4.1-192.168.4.127',
            ],
            true,
        ];

        $result['t40'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '172.16.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '172.16.0.2-172.16.255.253',
                static::TECH_VPN_IP__SUBNETS => '192.168.154.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.154.2-192.168.154.253',
                static::DEV__VIRTUAL_SUBNETS => '10.0.0.0/14',
                static::DEV__VIRTUAL_RANGES_ => '10.0.0.2-10.3.255.253',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.1.0.0/19',
                static::DEV__VPN_IP__RANGES_ => '10.1.0.2-10.1.31.254',
                static::TECH_VPN_IP__SUBNETS => '192.168.154.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.154.2-192.168.154.253',
                static::DEV__VIRTUAL_SUBNETS => '10.0.0.0/16',
                static::DEV__VIRTUAL_RANGES_ => '10.0.0.2-10.0.255.253',
            ],
            true,
        ];

        $result['t41'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '172.16.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '172.16.0.2-172.16.255.253',
                static::TECH_VPN_IP__SUBNETS => '192.168.154.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.154.2-192.168.154.253',
                static::DEV__VIRTUAL_SUBNETS => '10.0.0.0/14',
                static::DEV__VIRTUAL_RANGES_ => '10.0.0.2-10.0.255.253',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/19',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.2-10.3.31.254',
                static::TECH_VPN_IP__SUBNETS => '192.168.154.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.154.2-192.168.154.253',
                static::DEV__VIRTUAL_SUBNETS => '10.0.0.0/16',
                static::DEV__VIRTUAL_RANGES_ => '10.0.0.2-10.0.255.253',
            ],
            true,
        ];

        $result['t42'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '172.16.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '172.16.0.2-172.16.255.253',
                static::TECH_VPN_IP__SUBNETS => '192.168.154.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.154.2-192.168.154.253',
                static::DEV__VIRTUAL_SUBNETS => '10.0.0.0/14',
                static::DEV__VIRTUAL_RANGES_ => '10.0.0.2-10.0.255.253',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/19',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.2-10.3.31.254',
                static::TECH_VPN_IP__SUBNETS => '192.168.154.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.154.2-192.168.154.253',
                static::DEV__VIRTUAL_SUBNETS => '10.0.0.0/16',
                static::DEV__VIRTUAL_RANGES_ => '10.0.1.2-10.0.255.253',
            ],
            true,
        ];

        $result['t43'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.0.1.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.0.1.1-10.0.1.2',
                static::TECH_VPN_IP__SUBNETS => '192.168.0.0/23',
                static::TECH_VPN_IP__RANGES_ => '192.168.0.1-192.168.1.254',
                static::DEV__VIRTUAL_SUBNETS => '10.2.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.2.0.1-10.2.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '192.168.0.0/23',
                static::DEV__VPN_IP__RANGES_ => '192.168.0.1-192.168.1.254',
                static::TECH_VPN_IP__SUBNETS => '172.16.0.0/16',
                static::TECH_VPN_IP__RANGES_ => '172.16.0.1-172.16.255.254',
                static::DEV__VIRTUAL_SUBNETS => '10.2.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '10.2.0.1-10.3.0.255',
            ],
            false,
        ];

        $result['t44'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.2.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.2.0.0-10.2.255.255',
                static::TECH_VPN_IP__SUBNETS => '172.20.0.0/17',
                static::TECH_VPN_IP__RANGES_ => '172.20.0.0-172.20.127.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.10.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.10.1-192.168.10.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.2.128.0/17',
                static::DEV__VPN_IP__RANGES_ => '10.2.128.0-10.2.255.255,172.20.0.0-172.20.127.255',
                static::TECH_VPN_IP__SUBNETS => '10.2.0.0/17',
                static::TECH_VPN_IP__RANGES_ => '10.2.0.0-10.2.127.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.10.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.10.1-192.168.10.255',
            ],
            false,
        ];

        $result['t45'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.2.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.2.0.0-10.2.255.255',
                static::TECH_VPN_IP__SUBNETS => '172.20.0.0/17',
                static::TECH_VPN_IP__RANGES_ => '172.20.0.0-172.20.127.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.10.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.10.1-192.168.10.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.2.128.0/17,172.20.0.0/17',
                static::DEV__VPN_IP__RANGES_ => '10.2.128.0-10.2.255.255,172.20.0.0-172.20.127.255',
                static::TECH_VPN_IP__SUBNETS => '10.2.0.0/17',
                static::TECH_VPN_IP__RANGES_ => '10.2.0.0-10.2.127.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.10.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.10.1-192.168.10.255',
            ],
            false,
        ];

        $result['t46'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.0.1.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.0.1.1-10.0.2.2',
                static::TECH_VPN_IP__SUBNETS => '192.168.10.0/23',
                static::TECH_VPN_IP__RANGES_ => '192.168.10.1-192.168.11.254',
                static::DEV__VIRTUAL_SUBNETS => '192.168.12.0/23',
                static::DEV__VIRTUAL_RANGES_ => '192.168.12.1-192.168.13.254',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.0.1.0/24',
                static::DEV__VPN_IP__RANGES_ => '10.0.1.1-10.0.2.2',
                static::TECH_VPN_IP__SUBNETS => '192.168.10.0/24',
                static::TECH_VPN_IP__RANGES_ => '192.168.10.1-192.168.10.255',
                static::DEV__VIRTUAL_SUBNETS => '192.168.11.0/24',
                static::DEV__VIRTUAL_RANGES_ => '192.168.11.1-192.168.11.255',
            ],
            false,
        ];

        $result['t47'] = [
            'previous' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.0-10.3.255.255',
                static::TECH_VPN_IP__SUBNETS => '10.3.128.0/17',
                static::TECH_VPN_IP__RANGES_ => '10.3.128.0-10.3.255.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.0.1-172.16.0.255',
            ],
            'new' => [
                static::DEV__VPN_IP__SUBNETS => '10.3.0.0/16',
                static::DEV__VPN_IP__RANGES_ => '10.3.0.0-10.3.255.255',
                static::TECH_VPN_IP__SUBNETS => '10.3.128.0/16',
                static::TECH_VPN_IP__RANGES_ => '10.3.128.0-10.3.255.255',
                static::DEV__VIRTUAL_SUBNETS => '172.16.0.0/24',
                static::DEV__VIRTUAL_RANGES_ => '172.16.0.1-172.16.0.255',
            ],
            false,
        ];

        $result = static::removeDataRowKeys($result);

        return $result;
    }

    #[Group('full')]
    #[DataProvider('vpnConfigurationChangeTestsProvider')]
    public function testConfigurationChange(array $previousSubnetsConfiguration, array $newSubnetsConfiguration, bool $expectedToBeValid)
    {
        $this->loginApi('admin', 'admin');

        $this->jsonGet('/web/api/configuration/vpn');

        $this->assertResponseIsSuccessfulJson();

        $configurationArray = $this->getResponseContentAsArray();

        $configurationArray['devicesVpnNetworks'] = $newSubnetsConfiguration[static::DEV__VPN_IP__SUBNETS];
        $configurationArray['devicesVpnNetworksRanges'] = $newSubnetsConfiguration[static::DEV__VPN_IP__RANGES_];
        $configurationArray['techniciansVpnNetworks'] = $newSubnetsConfiguration[static::TECH_VPN_IP__SUBNETS];
        $configurationArray['techniciansVpnNetworksRanges'] = $newSubnetsConfiguration[static::TECH_VPN_IP__RANGES_];
        $configurationArray['devicesVirtualVpnNetworks'] = $newSubnetsConfiguration[static::DEV__VIRTUAL_SUBNETS];
        $configurationArray['devicesVirtualVpnNetworksRanges'] = $newSubnetsConfiguration[static::DEV__VIRTUAL_RANGES_];

        $this->jsonPost('/web/api/configuration/vpn', $configurationArray);

        if ($expectedToBeValid) {
            $this->assertResponseIsSuccessfulJson();

            $this->getEntityManager()->flush();

            $this->getEntityManager()->clear();

            $this->assertVpnSubnetsSameAsSubnetsConfiguration($newSubnetsConfiguration);
        } else {
            $this->assertResponse400();
        }
    }

    /**
     * Leaving secondary test method fo smoke tests to speed up the process. (With REST API ~27s, without ~4s).
     */
    #[Group('full')]
    #[Group('smoke')]
    #[DataProvider('vpnConfigurationChangeTestsProvider')]
    public function testConfigurationChangeMethodOnlyValidTestCases(array $previousSubnetsConfiguration, array $newSubnetsConfiguration, bool $expectedToBeValid)
    {
        if (!$expectedToBeValid) {
            $this->assertTrue(true);

            return;
        }
        $configuration = $this->updatePreviousConfigurationState($previousSubnetsConfiguration);

        $previousConfiguration = clone $configuration;

        $newConfiguration = $this->updateConfigurationState($newSubnetsConfiguration);

        $vpnAddressManager = $this->getService(VpnAddressManager::class);
        $this->assertNotNull($vpnAddressManager);

        $vpnAddressManager->processConfigurationSubnetsChange($previousConfiguration, $newConfiguration);

        $this->getEntityManager()->flush();

        $this->getEntityManager()->clear();

        $this->assertVpnSubnetsSameAsSubnetsConfiguration($newSubnetsConfiguration);
    }

    private function updatePreviousConfigurationState(array $previousConfiguration): Configuration
    {
        $this->clearVpnSubnetTable();

        $configuration = $this->updateConfigurationState($previousConfiguration);

        $vpnAddressManager = $this->getService(VpnAddressManager::class);
        $this->assertNotNull($vpnAddressManager);
        $vpnAddressManager->addConfigurationSubnets($configuration);

        $this->getEntityManager()->flush();

        $this->getEntityManager()->clear();

        $this->assertVpnSubnetsSameAsSubnetsConfiguration($previousConfiguration);

        return $configuration;
    }

    private function updateConfigurationState(array $subnetsConfiguration): Configuration
    {
        $configurationManager = $this->getConfigurationManager();
        $this->assertNotNull($configurationManager);

        $configuration = $configurationManager->getConfiguration();
        $configuration->setDevicesVpnNetworks($subnetsConfiguration[static::DEV__VPN_IP__SUBNETS]);
        $configuration->setDevicesVpnNetworksRanges($subnetsConfiguration[static::DEV__VPN_IP__RANGES_]);
        $configuration->setTechniciansVpnNetworks($subnetsConfiguration[static::TECH_VPN_IP__SUBNETS]);
        $configuration->setTechniciansVpnNetworksRanges($subnetsConfiguration[static::TECH_VPN_IP__RANGES_]);
        $configuration->setDevicesVirtualVpnNetworks($subnetsConfiguration[static::DEV__VIRTUAL_SUBNETS]);
        $configuration->setDevicesVirtualVpnNetworksRanges($subnetsConfiguration[static::DEV__VIRTUAL_RANGES_]);

        $this->getEntityManager()->persist($configuration);
        $this->getEntityManager()->flush();

        $this->getEntityManager()->clear();

        $configurationManager->refreshConfiguration();

        return $configuration;
    }

    private function clearVpnSubnetTable(): void
    {
        $vpnSubnetTableName = $this->getEntityManager()->getClassMetadata(VpnSubnet::class)->getTableName();

        $deleteQuery = 'DELETE FROM '.$vpnSubnetTableName.' WHERE 1';

        $stmt = $this->getEntityManager()->getConnection()->prepare($deleteQuery);
        $stmt->executeQuery();
    }

    private function assertVpnSubnetsSameAsSubnetsConfiguration(array $subnetsConfiguration): void
    {
        $vpnSubnetsArray = $this->getVpnSubnetsArray();

        $subnetKeys = [
            static::DEV__VPN_IP__SUBNETS,
            static::TECH_VPN_IP__SUBNETS,
            static::DEV__VIRTUAL_SUBNETS,
        ];

        foreach ($subnetKeys as $subnetKey) {
            $subnets = explode(',', $subnetsConfiguration[$subnetKey]);

            $subnetDiff = \array_diff($vpnSubnetsArray[$subnetKey], $subnets);
            $this->assertCount(
                0,
                $subnetDiff,
                "There are more subnets in database than in configuration for $subnetKey. ".print_r($subnetDiff, true)
            );

            $subnetDiff = \array_diff($subnets, $vpnSubnetsArray[$subnetKey]);
            $this->assertCount(
                0,
                $subnetDiff,
                "There are some subnets missing in database than in configuration for $subnetKey. ".print_r($subnetDiff, true)
            );
        }

        $rangeKeys = [
            static::DEV__VPN_IP__RANGES_,
            static::TECH_VPN_IP__RANGES_,
            static::DEV__VIRTUAL_RANGES_,
        ];

        foreach ($rangeKeys as $rangeKey) {
            $ranges = explode(',', $subnetsConfiguration[$rangeKey]);

            $vpnRanges = [];
            foreach ($vpnSubnetsArray[$rangeKey] as $range) {
                $vpnRanges[] = \long2ip($range['from']).'-'.\long2ip($range['to']);
            }

            $rangeDiff = \array_diff($vpnRanges, $ranges);
            $this->assertCount(
                0,
                $rangeDiff,
                "There are more ranges in database than in configuration for $rangeKey. ".print_r($rangeDiff, true)
            );

            $rangeDiff = \array_diff($ranges, $vpnRanges);
            $this->assertCount(
                0,
                $rangeDiff,
                "There are some ranges missing in database than in configuration for $rangeKey. ".print_r($rangeDiff, true)
            );
        }
    }

    // Method gets all records from VpnSubnet table and combines them into array of subnets and ranges
    private function getVpnSubnetsArray(): array
    {
        $limit = 100;
        $offset = 0;

        $vpnSubnetsArray = [
            static::DEV__VPN_IP__SUBNETS => [],
            static::DEV__VPN_IP__RANGES_ => [],
            static::TECH_VPN_IP__SUBNETS => [],
            static::TECH_VPN_IP__RANGES_ => [],
            static::DEV__VIRTUAL_SUBNETS => [],
            static::DEV__VIRTUAL_RANGES_ => [],
        ];

        do {
            // Query is limited to 10 results to limit memory usage
            $query = $this->getEntityManager()->getRepository(VpnSubnet::class)->createQueryBuilder('vs');
            $query->addOrderBy('vs.ipLong', 'ASC');
            $query->setMaxResults($limit);
            $query->setFirstResult($offset);
            $results = $query->getQuery()->getResult();
            // no more records - end of method
            if (0 == count($results)) {
                return $vpnSubnetsArray;
            }

            $offset += $limit;

            foreach ($results as $vpnSubnet) {
                $subnetKey = $this->getSubnetKey($vpnSubnet);
                $rangeKey = $this->getRangeKey($vpnSubnet);
                $vpnSubnetsArray[$subnetKey] = $this->fillSubnetArray($vpnSubnetsArray[$subnetKey], $vpnSubnet);
                $vpnSubnetsArray[$rangeKey] = $this->fillRangeArray($vpnSubnetsArray[$rangeKey], $vpnSubnet);
            }
        } while (1);

        return $vpnSubnetsArray;
    }

    private function fillSubnetArray(array $subnetsArray, VpnSubnet $vpnSubnet): array
    {
        if (!in_array($vpnSubnet->getNetwork(), $subnetsArray)) {
            $subnetsArray[] = $vpnSubnet->getNetwork();
        }

        return $subnetsArray;
    }

    private function fillRangeArray(array $rangesArray, VpnSubnet $vpnSubnet): array
    {
        $lastKey = array_key_last($rangesArray);
        if (null !== $lastKey && $rangesArray[$lastKey]['to'] === $vpnSubnet->getIpLong() - 1) {
            $rangesArray[$lastKey]['to'] = $vpnSubnet->getIpLong() + $vpnSubnet->getSize() - 1;
        } else {
            $rangesArray[] = [
                'from' => $vpnSubnet->getIpLong(),
                'to' => $vpnSubnet->getIpLong() + $vpnSubnet->getSize() - 1,
            ];
        }

        return $rangesArray;
    }

    private function getSubnetKey(VpnSubnet $vpnSubnet): string
    {
        switch ($vpnSubnet->getType()) {
            case VpnSubnetType::DEVICE_VPN_IP:
                return static::DEV__VPN_IP__SUBNETS;
            case VpnSubnetType::TECHNICIAN_VPN_IP:
                return static::TECH_VPN_IP__SUBNETS;
            case VpnSubnetType::DEVICE_VIRTUAL_IP:
                return static::DEV__VIRTUAL_SUBNETS;
        }
    }

    private function getRangeKey(VpnSubnet $vpnSubnet): string
    {
        switch ($vpnSubnet->getType()) {
            case VpnSubnetType::DEVICE_VPN_IP:
                return static::DEV__VPN_IP__RANGES_;
            case VpnSubnetType::TECHNICIAN_VPN_IP:
                return static::TECH_VPN_IP__RANGES_;
            case VpnSubnetType::DEVICE_VIRTUAL_IP:
                return static::DEV__VIRTUAL_RANGES_;
        }
    }
}
