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
use App\Entity\VpnSubnet;
use App\Enum\VpnSubnetType;
use App\Service\VpnAddressManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\Utility\Accessor;

/**
 * Testing VpnAddressManager class.
 */
#[Group('full')]
#[Group('smoke')]
#[Group('Unit')] // To be used in CI parallel tests
class VpnAddressManagerTest extends AbstractTestCase
{
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

    public function testSplitMergeVpnSubnet()
    {
        $vpnAddressManager = $this->getService(VpnAddressManager::class);
        $this->assertNotNull($vpnAddressManager);

        $this->assertVpnSubnetsExists([
            '10.19.128.0/18',
            '10.19.0.0/17',
        ]);

        $vpnSubnet = $this->getRepository(VpnSubnet::class)->findOneBy(['ip' => '10.19.0.0', 'cidr' => 17]);
        $this->assertNotNull($vpnSubnet);

        $vpnSubnet20 = Accessor::invoke($vpnAddressManager, 'splitSubnet', [$vpnSubnet, 20]);
        $this->assertNotNull($vpnSubnet20);
        $this->assertSame($vpnSubnet20->getCidr(), 20);

        $this->assertVpnSubnetsExists([
            '10.19.0.0/20',
            '10.19.16.0/20',
            '10.19.32.0/19',
            '10.19.64.0/18',
            '10.19.128.0/18',
        ]);

        $this->assertVpnSubnetsNotExists([
            '10.19.0.0/17',
        ]);
        // testing merge

        $vpnSubnet17 = Accessor::invoke($vpnAddressManager, 'mergeSubnet', [$vpnSubnet20]);
        $this->assertNotNull($vpnSubnet17);
        $this->assertSame($vpnSubnet17->getCidr(), 17);

        $this->assertVpnSubnetsExists([
            '10.19.0.0/17',
            '10.19.128.0/18',
        ]);

        $this->assertVpnSubnetsNotExists([
            '10.19.0.0/20',
            '10.19.16.0/20',
            '10.19.32.0/19',
            '10.19.64.0/18',
        ]);
    }

    public function testGetAddSubnetVpnSubnetNoSplitOrMerge()
    {
        $vpnAddressManager = $this->getService(VpnAddressManager::class);
        $this->assertNotNull($vpnAddressManager);

        $this->assertVpnSubnetsExists([
            '10.16.16.0/20',
        ]);

        $subnet20 = Accessor::invoke($vpnAddressManager, 'getSubnetByCidr', [20, VpnSubnetType::DEVICE_VIRTUAL_IP]);
        $this->assertNotNull($subnet20);
        $this->assertSame($subnet20, '10.16.16.0/20');

        $this->assertVpnSubnetsNotExists([
            '10.16.16.0/20',
        ]);

        list($ip, $cidrString) = explode('/', $subnet20);

        $subnet17 = Accessor::invoke($vpnAddressManager, 'addVirtualIpLongSubnet', [\ip2long($ip), 20]);
        $this->assertTrue($subnet17);

        $this->assertVpnSubnetsExists([
            '10.16.16.0/20',
        ]);
    }

    public function testGetAddSubnetVpnSubnetValidSplitAndMerge()
    {
        $vpnAddressManager = $this->getService(VpnAddressManager::class);
        $this->assertNotNull($vpnAddressManager);

        $this->assertVpnSubnetsExists([
            '10.16.0.2/31',
            '10.16.0.4/30',
            '10.19.255.248/30',
            '10.19.255.252/31',
        ]);

        $subnet2 = Accessor::invoke($vpnAddressManager, 'getSubnetByCidr', [31, VpnSubnetType::DEVICE_VIRTUAL_IP]);
        $this->assertNotNull($subnet2);
        $this->assertSame($subnet2, '10.16.0.2/31');

        $this->assertVpnSubnetsNotExists([
            '10.16.0.2/31',
        ]);

        $this->assertVpnSubnetsExists([
            '10.16.0.4/30',
            '10.19.255.248/30',
            '10.19.255.252/31',
        ]);

        $subnet2 = Accessor::invoke($vpnAddressManager, 'getSubnetByCidr', [31, VpnSubnetType::DEVICE_VIRTUAL_IP]);
        $this->assertNotNull($subnet2);
        $this->assertSame($subnet2, '10.19.255.252/31');

        $this->assertVpnSubnetsNotExists([
            '10.16.0.2/31',
            '10.19.255.252/31',
        ]);

        $this->assertVpnSubnetsExists([
            '10.16.0.4/30',
            '10.19.255.248/30',
        ]);

        $subnet2 = Accessor::invoke($vpnAddressManager, 'getSubnetByCidr', [31, VpnSubnetType::DEVICE_VIRTUAL_IP]);
        $this->assertNotNull($subnet2);
        $this->assertSame($subnet2, '10.16.0.4/31');

        $this->assertVpnSubnetsNotExists([
            '10.16.0.2/31',
            '10.19.255.252/31',
            '10.16.0.4/30',
        ]);

        $this->assertVpnSubnetsExists([
            '10.16.0.6/31',
            '10.19.255.248/30',
        ]);

        $subnet = Accessor::invoke($vpnAddressManager, 'addVirtualIpLongSubnet', [\ip2long('10.16.0.4'), 31]);
        $this->assertTrue($subnet);

        $this->assertVpnSubnetsNotExists([
            '10.16.0.2/31',
            '10.19.255.252/31',
        ]);

        $this->assertVpnSubnetsExists([
            '10.16.0.4/30',
            '10.19.255.248/30',
        ]);

        $subnet = Accessor::invoke($vpnAddressManager, 'addVirtualIpLongSubnet', [\ip2long('10.16.0.2'), 31]);
        $this->assertTrue($subnet);

        $this->assertVpnSubnetsNotExists([
            '10.19.255.252/31',
        ]);

        $this->assertVpnSubnetsExists([
            '10.16.0.2/31',
            '10.16.0.4/30',
            '10.19.255.248/30',
        ]);

        $subnet = Accessor::invoke($vpnAddressManager, 'addVirtualIpLongSubnet', [\ip2long('10.19.255.252'), 31]);
        $this->assertTrue($subnet);

        $this->assertVpnSubnetsExists([
            '10.16.0.2/31',
            '10.16.0.4/30',
            '10.19.255.248/30',
            '10.19.255.252/31',
        ]);
    }

    public static function vpnSubnetTestsProvider(): array
    {
        /*
        Provider format:
        - array of ranges - to setup before test
        - array of vpnSubnets - to setup before test
        - subnet to add
        - array of vpnSubnets that should exist after test
        - array of vpnSubnets that should NOT exist after test
        */
        $result = [];

        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            [],
            '10.1.0.0/31',
            ['10.1.0.0/31'],
            [],
        ];

        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            ['10.0.255.254/31'],
            '10.1.0.0/31',
            ['10.1.0.0/31', '10.0.255.254/31'],
            [],
        ];
        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            ['10.1.0.2/31'],
            '10.1.0.0/31',
            ['10.1.0.0/30'],
            ['10.1.0.2/31', '10.1.0.0/31'],
        ];
        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            [],
            '10.1.0.0/30',
            ['10.1.0.0/30'],
            [],
        ];
        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            ['10.1.0.2/31'],
            '10.1.0.0/30',
            ['10.1.0.0/30'],
            ['10.1.0.2/31'],
        ];
        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            ['10.1.0.0/31'],
            '10.1.0.0/30',
            ['10.1.0.0/30'],
            ['10.1.0.0/31'],
        ];
        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            ['10.1.0.0/30'],
            '10.1.0.4/30',
            ['10.1.0.0/29'],
            ['10.1.0.0/30', '10.1.0.4/30'],
        ];
        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            ['10.1.0.8/30'],
            '10.1.0.4/30',
            ['10.1.0.4/30', '10.1.0.8/30'],
            [],
        ];

        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            ['10.1.0.4/30', '10.1.0.8/29'],
            '10.1.0.0/30',
            ['10.1.0.0/28'],
            ['10.1.0.0/30', '10.1.0.4/30', '10.1.0.8/29'],
        ];
        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            ['10.1.0.0/30', '10.1.0.8/29'],
            '10.1.0.4/30',
            ['10.1.0.0/28'],
            ['10.1.0.0/30', '10.1.0.4/30', '10.1.0.8/29'],
        ];
        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            ['10.1.0.0/29', '10.1.0.12/30'],
            '10.1.0.8/30',
            ['10.1.0.0/28'],
            ['10.1.0.0/29', '10.1.0.8/30', '10.1.0.12/30'],
        ];
        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            ['10.1.0.0/29', '10.1.0.8/30'],
            '10.1.0.12/30',
            ['10.1.0.0/28'],
            ['10.1.0.0/29', '10.1.0.8/30', '10.1.0.12/30'],
        ];

        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            ['10.1.0.3/32', '10.1.0.4/30', '10.1.0.8/29'],
            '10.1.0.0/30',
            ['10.1.0.0/28'],
            ['10.1.0.3/32', '10.1.0.0/30', '10.1.0.4/30', '10.1.0.8/29'],
        ];
        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            ['10.1.0.5/32', '10.1.0.0/30', '10.1.0.8/29'],
            '10.1.0.4/30',
            ['10.1.0.0/28'],
            ['10.1.0.5/32', '10.1.0.0/30', '10.1.0.4/30', '10.1.0.8/29'],
        ];
        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            ['10.1.0.9/32', '10.1.0.10/32', '10.1.0.0/29', '10.1.0.12/30'],
            '10.1.0.8/30',
            ['10.1.0.0/28'],
            ['10.1.0.8/32', '10.1.0.9/32', '10.1.0.10/32', '10.1.0.0/29', '10.1.0.8/30', '10.1.0.12/30'],
        ];
        $result[] = [
            ['10.0.0.2-10.2.0.255'],
            ['10.1.0.13/32', '10.1.0.14/31', '10.1.0.0/29', '10.1.0.8/30'],
            '10.1.0.12/30',
            ['10.1.0.0/28'],
            ['10.1.0.13/32', '10.1.0.14/31', '10.1.0.0/29', '10.1.0.8/30', '10.1.0.12/30'],
        ];

        // ranges divided

        $result[] = [
            ['10.1.0.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            [],
            '10.1.0.0/31',
            ['10.1.0.0/31'],
            [],
        ];
        $result[] = [
            ['10.0.255.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            ['10.0.255.254/31'],
            '10.1.0.0/31',
            ['10.1.0.0/31', '10.0.255.254/31'],
            [],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            ['10.1.0.2/31'],
            '10.1.0.0/31',
            ['10.1.0.0/30'],
            ['10.1.0.2/31', '10.1.0.0/31'],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            [],
            '10.1.0.0/30',
            ['10.1.0.0/30'],
            [],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            ['10.1.0.2/31'],
            '10.1.0.0/30',
            ['10.1.0.0/30'],
            ['10.1.0.2/31'],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            ['10.1.0.0/31'],
            '10.1.0.0/30',
            ['10.1.0.0/30'],
            ['10.1.0.0/31'],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            ['10.1.0.0/30'],
            '10.1.0.4/30',
            ['10.1.0.0/29'],
            ['10.1.0.0/30', '10.1.0.4/30'],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            ['10.1.0.8/30'],
            '10.1.0.4/30',
            ['10.1.0.4/30', '10.1.0.8/30'],
            [],
        ];

        // Should this merge happen if subnets are in different ranges? - I think yes
        $result[] = [
            ['10.1.0.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            ['10.1.0.4/30', '10.1.0.8/29'],
            '10.1.0.0/30',
            ['10.1.0.0/28'],
            ['10.1.0.0/30', '10.1.0.4/30', '10.1.0.8/29'],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            ['10.1.0.0/30', '10.1.0.8/29'],
            '10.1.0.4/30',
            ['10.1.0.0/28'],
            ['10.1.0.0/30', '10.1.0.4/30', '10.1.0.8/29'],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            ['10.1.0.0/29', '10.1.0.12/30'],
            '10.1.0.8/30',
            ['10.1.0.0/28'],
            ['10.1.0.0/29', '10.1.0.8/30', '10.1.0.12/30'],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            ['10.1.0.0/29', '10.1.0.8/30'],
            '10.1.0.12/30',
            ['10.1.0.0/28'],
            ['10.1.0.0/29', '10.1.0.8/30', '10.1.0.12/30'],
        ];

        $result[] = [
            ['10.1.0.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            ['10.1.0.3/32', '10.1.0.4/30', '10.1.0.8/29'],
            '10.1.0.0/30',
            ['10.1.0.0/28'],
            ['10.1.0.3/32', '10.1.0.0/30', '10.1.0.4/30', '10.1.0.8/29'],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            ['10.1.0.5/32', '10.1.0.0/30', '10.1.0.8/29'],
            '10.1.0.4/30',
            ['10.1.0.0/28'],
            ['10.1.0.5/32', '10.1.0.0/30', '10.1.0.4/30', '10.1.0.8/29'],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            ['10.1.0.9/32', '10.1.0.10/32', '10.1.0.0/29', '10.1.0.12/30'],
            '10.1.0.8/30',
            ['10.1.0.0/28'],
            ['10.1.0.8/32', '10.1.0.9/32', '10.1.0.10/32', '10.1.0.0/29', '10.1.0.8/30', '10.1.0.12/30'],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.7,10.1.0.8-10.1.0.15'],
            ['10.1.0.13/32', '10.1.0.14/31', '10.1.0.0/29', '10.1.0.8/30'],
            '10.1.0.12/30',
            ['10.1.0.0/28'],
            ['10.1.0.13/32', '10.1.0.14/31', '10.1.0.0/29', '10.1.0.8/30', '10.1.0.12/30'],
        ];

        // ranges are separated

        $result[] = [
            ['10.1.0.0-10.1.0.6,10.1.0.9-10.1.0.15'],
            [],
            '10.1.0.0/31',
            ['10.1.0.0/31'],
            [],
        ];
        $result[] = [
            ['10.0.255.0-10.1.0.6,10.1.0.9-10.1.0.15'],
            ['10.0.255.254/31'],
            '10.1.0.0/31',
            ['10.1.0.0/31', '10.0.255.254/31'],
            [],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.6,10.1.0.9-10.1.0.15'],
            ['10.1.0.2/31'],
            '10.1.0.0/31',
            ['10.1.0.0/30'],
            ['10.1.0.2/31', '10.1.0.0/31'],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.6,10.1.0.9-10.1.0.15'],
            [],
            '10.1.0.0/30',
            ['10.1.0.0/30'],
            [],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.6,10.1.0.9-10.1.0.15'],
            ['10.1.0.2/31'],
            '10.1.0.0/30',
            ['10.1.0.0/30'],
            ['10.1.0.2/31'],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.6,10.1.0.9-10.1.0.15'],
            ['10.1.0.0/31'],
            '10.1.0.0/30',
            ['10.1.0.0/30'],
            ['10.1.0.0/31'],
        ];

        $result[] = [
            ['10.1.0.0-10.1.0.6,10.1.0.9-10.1.0.15'],
            ['10.1.0.0/30'],
            '10.1.0.4/30',
            ['10.1.0.0/30', '10.1.0.4/31', '10.1.0.6/32'],
            ['10.1.0.0/29'],
        ];
        $result[] = [
            ['10.1.0.0-10.1.0.6,10.1.0.9-10.1.0.15'],
            ['10.1.0.9/32', '10.1.0.10/31'],
            '10.1.0.4/30',
            ['10.1.0.4/31', '10.1.0.6/32', '10.1.0.9/32', '10.1.0.10/31'],
            ['10.1.0.4/30', '10.1.0.8/30'],
        ];

        $result[] = [
            ['10.1.0.0-10.1.0.6,10.1.0.9-10.1.0.15'],
            ['10.1.0.0/32', '10.1.0.2/32', '10.1.0.5/32', '10.1.0.6/32', '10.1.0.9/32', '10.1.0.10/31'],
            '10.1.0.0/28',
            ['10.1.0.0/30', '10.1.0.4/31', '10.1.0.6/32', '10.1.0.9/32', '10.1.0.10/31', '10.1.0.12/30'],
            ['10.1.0.0/28'],
        ];

        return $result;
    }

    // TODO add test case to provider to check if code can handle merging subnets from different networks or types
    // TODO can spliting and other functions be tested that way?
    #[DataProvider('vpnSubnetTestsProvider')]
    public function testGetAddSubnetVpnSubnetInValidSplitAndMerge(array $ranges, array $setupVpnSubnets, string $subnetToAdd, array $expectedVpnSubnets, array $notExpectedVpnSubnets)
    {
        $vpnAddressManager = $this->getService(VpnAddressManager::class);
        $this->assertNotNull($vpnAddressManager);
        $configurationManager = $this->getConfigurationManager();
        $this->assertNotNull($configurationManager);

        $configuration = $configurationManager->getConfiguration();
        $configuration->setDevicesVirtualVpnNetworks('10.0.0.0/14');
        $configuration->setDevicesVirtualVpnNetworksRanges(implode(',', $ranges));
        $this->getEntityManager()->persist($configuration);
        $this->getEntityManager()->flush();

        $configurationManager->refreshConfiguration();

        $vpnSubnetTableName = $this->getEntityManager()->getClassMetadata(VpnSubnet::class)->getTableName();

        $deleteQuery = 'DELETE FROM '.$vpnSubnetTableName.' WHERE 1';

        $stmt = $this->getEntityManager()->getConnection()->prepare($deleteQuery);
        $stmt->executeQuery();

        foreach ($setupVpnSubnets as $setupVpnSubnet) {
            list($ip, $cidrString) = explode('/', $setupVpnSubnet);
            $subnet = Accessor::invoke($vpnAddressManager, 'addVirtualIpLongSubnet', [\ip2long($ip), $cidrString]);
            $this->assertTrue($subnet);
        }

        $this->assertVpnSubnetsExists($setupVpnSubnets);
        $this->assertVpnSubnetsNotExists([$subnetToAdd]);

        list($ip, $cidrString) = explode('/', $subnetToAdd);
        $subnet = Accessor::invoke($vpnAddressManager, 'addVirtualIpLongSubnet', [\ip2long($ip), $cidrString]);
        $this->assertTrue($subnet);

        $this->assertVpnSubnetsExists($expectedVpnSubnets);
        $this->assertVpnSubnetsNotExists($notExpectedVpnSubnets);
    }

    /**
     * Method asserts if all VpnSubnets in array (as CIDR e.g. 10.0.0.0/14) exists.
     */
    private function assertVpnSubnetsExists(array $vpnSubnets): void
    {
        $this->assertVpnSubnets($vpnSubnets, true);
    }

    /**
     * Method asserts if all VpnSubnets in array (as CIDR e.g. 10.0.0.0/14)  doesn't exists.
     */
    private function assertVpnSubnetsNotExists(array $vpnSubnets): void
    {
        $this->assertVpnSubnets($vpnSubnets, false);
    }

    /**
     * Method asserts if all VpnSubnets in array (as CIDR e.g. 10.0.0.0/14) exists or doesn't exists.
     */
    private function assertVpnSubnets(array $vpnSubnets, bool $exists): void
    {
        foreach ($vpnSubnets as $vpnSubnet) {
            list($ip, $cidrString) = explode('/', $vpnSubnet);
            $vpnSubnetObject = $this->getRepository(VpnSubnet::class)->findOneBy(['ip' => $ip, 'cidr' => $cidrString]);
            if ($exists) {
                $this->assertNotNull($vpnSubnetObject, 'Subnet "'.$vpnSubnet.'" should exist');
            } else {
                $this->assertNull($vpnSubnetObject, 'Subnet "'.$vpnSubnet.'" should NOT exist');
            }
        }
    }
}
