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

namespace Tests\Suites\Scenario\WebApi;

use App\Entity\Certificate;
use App\Entity\DeviceType;
use App\Enum\CertificateEntity;
use App\Service\PkiProviderFactory;
use App\Service\VpnProviderFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\ApiClient\ApiClientAssert as Assert;
use Tests\Utilities\Mock\PkiProvider\DevicesMockPkiProvider;
use Tests\Utilities\Mock\PkiProvider\TechniciansMockPkiProvider;
use Tests\Utilities\Mock\VpnProvider\MockVpnProvider;

/**
 * This is a scenario test. They are numbered instead of named by design.
 * It has one long method divided into several steps.
 *
 * Scenario 3 tests on VPN Security Suite license, SCEP and VPN configured and using VPN Container Client.
 * Mocks PKI and VPN providers. It has following steps.
 * 1. Login as admin
 * 2. Create access tags (Access tag 1, Access tag 2, Access tag 3)
 * 3. Create enabled Device 1 with AT1 and ED1, ED2 with AT1
 * 4. Create enabled Device 2 with AT1 and AT2
 * 5. Create enabled Device 3 with AT2 and ED3 with AT3
 * 6. Create disabled Device 4 with no access tags
 * 7. Create VPN Security Suite user 1 with AT1 (VSS1)
 * 8. Create VPN Security Suite user 2 with AT2 (VSS2)
 * 9. Login as VSS1
 * 10. Verify lists (device, endpoint device)
 * 11. Update VPN connection status
 * 12. Connect to Device 1 and ED 1
 * 13. Verify lists (vpn connection)
 * 14. Login as VSS2
 * 15. Verify lists (device, endpoint device, vpn connection)
 * 16. Connect to Device 3
 * 17. Verify lists (vpn connection)
 * 18. Connect to Device 1 and expect 404
 * 19. Connect to ED 3 and expect 404
 * 20. Verify lists (vpn connection)
 * 21. Disconnect connection VSS2 - Device 3
 * 22. Disconnect connection VSS1 - Device 1 and expect 404
 * 23. Verify lists (vpn connection)
 * 24. Login as admin
 * 25. Verify lists (vpn connection)
 * 26. Disconnect connection VSS1 - Device 1
 * 27. Verify lists (vpn connection)
 */
#[Group('full')]
#[Group('smoke')]
#[AllowMockObjectsWithoutExpectations]
class Scenario3Test extends AbstractTestCase
{
    public static function getFixtureGroups(): array
    {
        return [
            'prod',
            TestFixtures\Configuration\ScepFixtures::class,
            TestFixtures\Configuration\VpnFixtures::class,
            TestFixtures\Configuration\DisablePasswordRequirementsFixtures::class,
        ];
    }

    public function mock(): void
    {
        $mock = $this->createMock(VpnProviderFactory::class);
        $mock
            ->expects(self::any())
            ->method('getProvider')
            ->willReturnCallback(function () {
                return new MockVpnProvider();
            })
        ;
        static::getContainer()->set(VpnProviderFactory::class, $mock);

        $mock = $this->createMock(PkiProviderFactory::class);
        $mock
            ->expects(self::any())
            ->method('getProvider')
            ->willReturnCallback(function (Certificate $certificate) {
                $certificateType = $certificate->getCertificateType();

                switch ($certificateType->getCertificateEntity()) {
                    case CertificateEntity::DEVICE:
                        return new DevicesMockPkiProvider();
                    case CertificateEntity::USER:
                        return new TechniciansMockPkiProvider();
                }

                throw new \Exception('Cannot determine mock PKI provider');
            })
        ;
        static::getContainer()->set(PkiProviderFactory::class, $mock);
    }

    public function testScenario()
    {
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'VPN Container Client']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        // Ids are stored under named key i.e. 'at1', 'at2', 'device1' corresponding to order in which they are created.
        $atIds = [];
        $deviceIds = [];
        $endpointDeviceIds = [];
        $userIds = [];
        $connectionIds = [];

        // 1. Login as admin
        $this->loginApi('admin', 'admin');

        // 2. Create access tags (Access tag 1, Access tag 2, Access tag 3)
        for ($i = 1; $i <= 3; ++$i) {
            $this->getApiClient()->request(
                uri: '/web/api/accesstag/create',
                parameters: [
                    'name' => 'Access tag '.$i,
                ],
            );
            $atIds['at'.$i] = $this->getResponseValue('id');
        }

        // 3. Create enabled Device 1 with AT1 and ED1, ED2 with AT1
        $this->getApiClient()->request(
            uri: '/web/api/device/create',
            parameters: [
                'name' => 'Device 1',
                'masqueradeType' => 'disabled',
                'virtualSubnetCidr' => 29,
                'enabled' => true,
                'accessTags' => [
                    $atIds['at1'],
                ],
                'endpointDevices' => [
                    [
                        'name' => 'ED1',
                        'physicalIp' => '192.168.1.1',
                        'virtualIpHostPart' => 1,
                        'accessTags' => [
                            $atIds['at1'],
                        ],
                    ],
                    [
                        'name' => 'ED2',
                        'physicalIp' => '192.168.1.2',
                        'virtualIpHostPart' => 2,
                        'accessTags' => [
                            $atIds['at1'],
                        ],
                    ],
                ],
            ],
        );
        $deviceIds['device1'] = $this->getResponseValue('id');
        $endpointDeviceIds['ed1'] = $this->getResponseValue('endpointDevices.0.id');
        $endpointDeviceIds['ed2'] = $this->getResponseValue('endpointDevices.1.id');

        // 4. Create enabled Device 2 with AT1 and AT2
        $this->getApiClient()->request(
            uri: '/web/api/device/create',
            parameters: [
                'name' => 'Device 2',
                'masqueradeType' => 'disabled',
                'virtualSubnetCidr' => 29,
                'enabled' => true,
                'accessTags' => [
                    $atIds['at1'],
                    $atIds['at2'],
                ],
            ],
        );
        $deviceIds['device2'] = $this->getResponseValue('id');

        // 5. Create enabled Device 3 with AT2 and ED3 with AT3
        $this->getApiClient()->request(
            uri: '/web/api/device/create',
            parameters: [
                'name' => 'Device 3',
                'masqueradeType' => 'disabled',
                'virtualSubnetCidr' => 29,
                'enabled' => true,
                'accessTags' => [
                    $atIds['at2'],
                ],
                'endpointDevices' => [
                    [
                        'name' => 'ED3',
                        'physicalIp' => '192.168.1.1',
                        'virtualIpHostPart' => 1,
                        'accessTags' => [
                            $atIds['at3'],
                        ],
                    ],
                ],
            ],
        );
        $deviceIds['device3'] = $this->getResponseValue('id');
        $endpointDeviceIds['ed3'] = $this->getResponseValue('endpointDevices.0.id');

        // 6. Create disabled Device 4 with no access tags
        $this->getApiClient()->request(
            uri: '/web/api/device/create',
            parameters: [
                'name' => 'Device 4',
                'masqueradeType' => 'disabled',
                'virtualSubnetCidr' => 29,
            ],
        );
        $deviceIds['device4'] = $this->getResponseValue('id');

        // 7. Create VPN Security Suite user 1 with AT1 (VSS1)
        $this->getApiClient()->request(
            uri: '/web/api/user/create',
            parameters: fn () => [
                'username' => 'vss1',
                'enabled' => true,
                'roleVpn' => true,
                'plainPassword' => 'vss1',
                'plainPasswordRepeat' => 'vss1',
                'accessTags' => [
                    $atIds['at1'],
                ],
            ],
        );
        $userIds['vss1'] = $this->getResponseValue('id');

        // 8. Create VPN Security Suite user 2 with AT2 (VSS2)
        $this->getApiClient()->request(
            uri: '/web/api/user/create',
            parameters: fn () => [
                'username' => 'vss2',
                'enabled' => true,
                'roleVpn' => true,
                'plainPassword' => 'vss2',
                'plainPasswordRepeat' => 'vss2',
                'accessTags' => [
                    $atIds['at2'],
                ],
            ],
        );
        $userIds['vss2'] = $this->getResponseValue('id');

        // 9. Login as VSS1
        $this->loginApi('vss1', 'vss1');

        // 10. Verify lists (device, endpoint device)
        $this->getApiClient()->request(
            uri: '/web/api/device/list',
            asserts: [
                'rowCount' => 2,
                'results.0.id' => $deviceIds['device1'],
                'results.1.id' => $deviceIds['device2'],
            ],
        );
        $this->getApiClient()->request(
            uri: '/web/api/deviceendpointdevice/list',
            asserts: [
                'rowCount' => 2,
                'results.0.id' => $endpointDeviceIds['ed1'],
                'results.1.id' => $endpointDeviceIds['ed2'],
            ],
        );

        // 11. Update VPN connection status
        $this->getApiClient()->request(
            uri: '/web/api/vpn/connection/status',
        );

        // 12. Connect to Device 1 and ED 1
        $this->getApiClient()->request(
            uri: '/web/api/device/{id}/open/vpnconnection',
            uriParameters: [
                'id' => $deviceIds['device1'],
            ],
        );
        $this->getApiClient()->request(
            uri: '/web/api/deviceendpointdevice/{id}/open/vpnconnection',
            uriParameters: [
                'id' => $endpointDeviceIds['ed1'],
            ],
        );

        // 13. Verify lists (vpn connection)
        $this->getApiClient()->request(
            uri: '/web/api/vpnconnection/list',
            asserts: [
                'rowCount' => 2,
                'results.0.user.id' => $userIds['vss1'],
                'results.0.target.id' => $deviceIds['device1'],
                'results.1.user.id' => $userIds['vss1'],
                'results.1.target.id' => $endpointDeviceIds['ed1'],
            ],
        );
        $connectionIds['c1'] = $this->getResponseValue('results.0.id');
        $connectionIds['c2'] = $this->getResponseValue('results.1.id');

        // 14. Login as VSS2
        $this->loginApi('vss2', 'vss2');

        // 15. Verify lists (device, endpoint device, vpn connection)
        $this->getApiClient()->request(
            uri: '/web/api/device/list',
            asserts: [
                'rowCount' => 2,
                'results.0.id' => $deviceIds['device2'],
                'results.1.id' => $deviceIds['device3'],
            ],
        );
        $this->getApiClient()->request(
            uri: '/web/api/deviceendpointdevice/list',
            asserts: [
                'rowCount' => 0,
            ],
        );
        $this->getApiClient()->request(
            uri: '/web/api/vpnconnection/list',
            asserts: [
                'rowCount' => 0,
            ],
        );

        // 16. Connect to Device 3
        $this->getApiClient()->request(
            uri: '/web/api/device/{id}/open/vpnconnection',
            uriParameters: [
                'id' => $deviceIds['device3'],
            ],
        );

        // 17. Verify lists (vpn connection)
        $this->getApiClient()->request(
            uri: '/web/api/vpnconnection/list',
            asserts: [
                'rowCount' => 1,
                'results.0.user.id' => $userIds['vss2'],
                'results.0.target.id' => $deviceIds['device3'],
            ],
        );
        $connectionIds['c3'] = $this->getResponseValue('results.0.id');

        // 18. Connect to Device 1 and expect 404
        $this->getApiClient()->request(
            uri: '/web/api/device/{id}/open/vpnconnection',
            uriParameters: [
                'id' => $deviceIds['device1'],
            ],
            asserts: fn () => [
                 Assert::RESPONSE_404,
            ],
        );

        // 19. Connect to ED 3 and expect 404
        $this->getApiClient()->request(
            uri: '/web/api/deviceendpointdevice/{id}/open/vpnconnection',
            uriParameters: [
                'id' => $endpointDeviceIds['ed3'],
            ],
            asserts: fn () => [
                 Assert::RESPONSE_404,
            ],
        );

        // 20. Verify lists (vpn connection)
        $this->getApiClient()->request(
            uri: '/web/api/vpnconnection/list',
            asserts: [
                'rowCount' => 1,
                'results.0.user.id' => $userIds['vss2'],
                'results.0.target.id' => $deviceIds['device3'],
            ],
        );

        // 21. Disconnect connection VSS2 - Device 3
        $this->getApiClient()->request(
            uri: '/web/api/vpnconnection/{id}/close/vpnconnection',
            uriParameters: [
                'id' => $connectionIds['c3'],
            ],
        );

        // 22. Disconnect connection VSS1 - Device 1 and expect 404
        $this->getApiClient()->request(
            uri: '/web/api/vpnconnection/{id}/close/vpnconnection',
            uriParameters: [
                'id' => $connectionIds['c1'],
            ],
            asserts: fn () => [
                Assert::RESPONSE_404,
            ],
        );

        // 23. Verify lists (vpn connection)
        $this->getApiClient()->request(
            uri: '/web/api/vpnconnection/list',
            asserts: [
                'rowCount' => 0,
            ],
        );

        // 24. Login as admin
        $this->loginApi('admin', 'admin');

        // 25. Verify lists (vpn connection)
        $this->getApiClient()->request(
            uri: '/web/api/vpnconnection/list',
            asserts: [
                'rowCount' => 2,
                'results.0.user.id' => $userIds['vss1'],
                'results.0.target.id' => $deviceIds['device1'],
                'results.1.user.id' => $userIds['vss1'],
                'results.1.target.id' => $endpointDeviceIds['ed1'],
            ],
        );

        // 26. Disconnect connection VSS1 - Device 1
        $this->getApiClient()->request(
            uri: '/web/api/vpnconnection/{id}/close/vpnconnection',
            uriParameters: [
                'id' => $connectionIds['c1'],
            ],
        );

        // 27. Verify lists (vpn connection)
        $this->getApiClient()->request(
            uri: '/web/api/vpnconnection/list',
            asserts: [
                'rowCount' => 1,
                'results.0.user.id' => $userIds['vss1'],
                'results.0.target.id' => $endpointDeviceIds['ed1'],
            ],
        );
    }
}
