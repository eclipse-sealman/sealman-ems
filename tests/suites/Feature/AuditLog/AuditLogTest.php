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

namespace Tests\Suites\Feature\AuditLog;

use App\DataFixtures as ProdFixtures;
use App\Entity\AccessTag;
use App\Entity\CertificateType;
use App\Entity\Config;
use App\Entity\Device;
use App\Entity\DeviceEndpointDevice;
use App\Entity\DeviceType;
use App\Entity\DeviceVariable;
use App\Entity\MaintenanceSchedule;
use App\Entity\Template;
use App\Entity\User;
use App\Entity\VpnConnection;
use App\Entity\VpnLog;
use App\Enum\AuditLogChangeType;
use App\Enum\CertificateBehavior;
use App\Enum\CertificateEntity;
use App\Enum\ConfigGenerator;
use App\Enum\Feature;
use App\Enum\LogLevel;
use App\Enum\PkiType;
use App\EventListener\AuditableListener;
use App\Helper\UptimeConverter;
use App\Model\AuditableInterface;
use Carve\ApiBundle\Helper\Arr;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\Feature\AuditLog\AssertAuditLogTrait;
use Tests\Utilities\Feature\AuditLog\AssertDateTime;

/**
 * This test suite aims to test each type of auditable property for every change type (create, update, delete) to ensure all cases are covered.
 *
 * It also test encrypted.
 *
 * Upload is not tested as it is treated as a string field.
 */
#[Group('full')]
#[Group('smoke')]
class AuditLogTest extends AbstractTestCase
{
    use AssertAuditLogTrait;

    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\CertificateTypeFixtures::class,
            ProdFixtures\ConfigurationFixtures::class,
            ProdFixtures\DeviceAuthenticationFixtures::class,
            ProdFixtures\DeviceTypeFixtures::class,
            ProdFixtures\UserDeviceSecretCredentialsFixtures::class,
            ProdFixtures\UserDeviceX509CredentialsFixtures::class,
            ProdFixtures\UserFixtures::class,
            TestFixtures\Configuration\VpnFixtures::class,
            TestFixtures\User\AdminFixtures::class,
        ];
    }

    public function testSelfReferenced()
    {
        $this->loginApi('admin', 'admin');

        // Prepare new admin that will be removed at the end
        $username = 'admin-selfreferenced';
        $this->jsonPost('/web/api/user/create', [
            'username' => $username,
            'plainPassword' => $username,
            'plainPasswordRepeat' => $username,
            'enabled' => true,
            'roleAdmin' => true,
        ]);

        $admin = $this->getRepository(User::class)->findOneBy([
            'username' => $username,
        ]);
        $this->loginApi($username, $username);

        // There is no testable WebUI create case at this point

        $id = $admin->getId();
        $disablePasswordExpire = true;
        // Update
        $this->jsonPost('/web/api/user/'.$id, [
            'username' => $username,
            'disablePasswordExpire' => $disablePasswordExpire,
            'enabled' => true,
            'roleAdmin' => true,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(User::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'disablePasswordExpire' => false,
            'updatedBy' => $id,
        ], [
            'id' => $id,
            'disablePasswordExpire' => $disablePasswordExpire,
            'updatedBy' => $id,
        ]);

        // Restart kernel to avoid any issues with blameable behaviour (AuditLog::createdBy would be set as $admin)
        $this->tearDown();
        $this->setUp();

        $admin = $this->getRepository(User::class)->findOneBy([
            'username' => $username,
        ]);
        // Delete is not possible via WebUI
        $this->getEntityManager()->remove($admin);
        $this->getEntityManager()->flush();
        $this->getService(AuditableListener::class)->flushChanges();

        $change = $this->findChange(User::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'disablePasswordExpire' => $disablePasswordExpire,
            'updatedBy' => $id,
        ]);
    }

    public function testBlameable()
    {
        $admin = $this->getRepository(User::class)->findOneBy([
            'username' => 'admin',
        ]);
        $admin2 = $this->getRepository(User::class)->findOneBy([
            'username' => 'admin2-fixtures',
        ]);

        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => 'TK800',
        ]);

        // Create
        $this->jsonPost('/web/api/template/create', [
            'deviceType' => $deviceType->getId(),
            'name' => 'Name blameable 1',
        ]);

        $this->assertResponseIsSuccessfulJson();

        $id = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($id);

        $change = $this->findChange(Template::class, $id);
        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
            'id' => $id,
            'createdBy' => $admin->getId(),
            'updatedBy' => $admin->getId(),
        ]);

        $this->loginApi('admin2-fixtures', 'admin2-fixtures');

        // Update
        $this->jsonPost('/web/api/template/'.$id, [
            'name' => 'Name blameable 2',
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(Template::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'createdBy' => $admin->getId(),
            'updatedBy' => $admin->getId(),
        ], [
            'id' => $id,
            'createdBy' => $admin->getId(),
            'updatedBy' => $admin2->getId(),
        ]);

        // Delete
        $this->jsonDelete('/web/api/template/'.$id);
        $this->assertResponseIsSuccessful();

        $change = $this->findChange(Template::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'createdBy' => $admin->getId(),
            'updatedBy' => $admin2->getId(),
        ]);
    }

    public function testTimestampable()
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => 'TK800',
        ]);

        // Create
        $this->jsonPost('/web/api/template/create', [
            'deviceType' => $deviceType->getId(),
            'name' => 'Name blameable 1',
        ]);

        $this->assertResponseIsSuccessfulJson();

        $id = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($id);

        $createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $change = $this->findChange(Template::class, $id);
        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
            'id' => $id,
            'createdAt' => new AssertDateTime($createdAt),
            'updatedAt' => new AssertDateTime($createdAt),
        ]);

        // Update
        $this->jsonPost('/web/api/template/'.$id, [
            'name' => 'Name blameable 2',
        ]);
        $this->assertResponseIsSuccessfulJson();

        $updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $change = $this->findChange(Template::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'createdAt' => new AssertDateTime($createdAt),
            'updatedAt' => new AssertDateTime($createdAt),
        ], [
            'id' => $id,
            'createdAt' => new AssertDateTime($createdAt),
            'updatedAt' => new AssertDateTime($updatedAt),
        ]);

        // Delete
        $this->jsonDelete('/web/api/template/'.$id);
        $this->assertResponseIsSuccessful();

        $change = $this->findChange(Template::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'createdAt' => new AssertDateTime($createdAt),
            'updatedAt' => new AssertDateTime($updatedAt),
        ]);
    }

    public function testString()
    {
        $this->loginApi('admin', 'admin');

        // Create
        $name = 'Access tag 1';
        $this->jsonPost('/web/api/accesstag/create', [
            'name' => $name,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $id = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($id);

        $change = $this->findChange(AccessTag::class, $id);
        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
            'id' => $id,
            'name' => $name,
        ]);

        // Update
        $newName = 'Access tag 2';
        $this->jsonPost('/web/api/accesstag/'.$id, [
            'name' => $newName,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(AccessTag::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'name' => $name,
        ], [
            'id' => $id,
            'name' => $newName,
        ]);

        // Delete
        $this->jsonDelete('/web/api/accesstag/'.$id);
        $this->assertResponseIsSuccessful();

        $change = $this->findChange(AccessTag::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'name' => $newName,
        ]);
    }

    public function testInteger()
    {
        $this->loginApi('admin', 'admin');

        // Create
        $dayOfMonth = 15;
        $this->jsonPost('/web/api/maintenanceschedule/create', [
            'name' => 'Name',
            'backupDatabase' => true,
            'dayOfMonth' => $dayOfMonth,
            'dayOfWeek' => -1,
            'hour' => -1,
            'minute' => -1,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $id = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($id);

        $change = $this->findChange(MaintenanceSchedule::class, $id);
        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
            'id' => $id,
            'dayOfMonth' => $dayOfMonth,
        ]);

        // Update
        $newDayOfMonth = 18;
        $this->jsonPost('/web/api/maintenanceschedule/'.$id, [
            'name' => 'Name',
            'backupDatabase' => true,
            'dayOfMonth' => $newDayOfMonth,
            'dayOfWeek' => -1,
            'hour' => -1,
            'minute' => -1,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(MaintenanceSchedule::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'dayOfMonth' => $dayOfMonth,
        ], [
            'id' => $id,
            'dayOfMonth' => $newDayOfMonth,
        ]);

        // Delete
        $this->jsonDelete('/web/api/maintenanceschedule/'.$id);
        $this->assertResponseIsSuccessful();

        $change = $this->findChange(MaintenanceSchedule::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'dayOfMonth' => $newDayOfMonth,
        ]);
    }

    public function testBoolean()
    {
        $this->loginApi('admin', 'admin');

        // Create
        $uploadEnabled = true;
        $downloadEnabled = false;
        $this->jsonPost('/web/api/certificatetype/create', [
            'uploadEnabled' => $uploadEnabled,
            'downloadEnabled' => $downloadEnabled,
            'name' => 'Name',
            'commonNamePrefix' => 'pre',
            'variablePrefix' => 'pre',
            'certificateEntity' => CertificateEntity::USER,
            'enabledBehaviour' => CertificateBehavior::NONE,
            'disabledBehaviour' => CertificateBehavior::NONE,
            'pkiType' => PkiType::NONE,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $id = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($id);

        $change = $this->findChange(CertificateType::class, $id);
        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
            'id' => $id,
            'uploadEnabled' => $uploadEnabled,
            'downloadEnabled' => $downloadEnabled,
        ]);

        // Update
        $newUploadEnabled = false;
        $newDownloadEnabled = true;
        $this->jsonPost('/web/api/certificatetype/'.$id, [
            'uploadEnabled' => $newUploadEnabled,
            'downloadEnabled' => $newDownloadEnabled,
            'name' => 'Name',
            'commonNamePrefix' => 'pre',
            'variablePrefix' => 'pre',
            'enabledBehaviour' => CertificateBehavior::NONE,
            'disabledBehaviour' => CertificateBehavior::NONE,
            'pkiType' => PkiType::NONE,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(CertificateType::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'uploadEnabled' => $uploadEnabled,
            'downloadEnabled' => $downloadEnabled,
        ], [
            'id' => $id,
            'uploadEnabled' => $newUploadEnabled,
            'downloadEnabled' => $newDownloadEnabled,
        ]);

        // Delete
        $this->jsonDelete('/web/api/certificatetype/'.$id);
        $this->assertResponseIsSuccessful();

        $change = $this->findChange(CertificateType::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'uploadEnabled' => $newUploadEnabled,
            'downloadEnabled' => $newDownloadEnabled,
        ]);
    }

    /**
     * Array is tested using entityManager instead of logged in user due to lack of endpoint that allows it.
     *
     * Adjust this test when it will be possible to test array using an endpoint.
     */
    public function testArray()
    {
        $rules = [
            'a' => 'b',
            'b' => [1, 2, 3],
            'c' => [
                'c1' => 'c2',
            ],
        ];

        $vpnConnection = new VpnConnection();
        $vpnConnection->setConnectionFirewallRules($rules);

        $this->getEntityManager()->persist($vpnConnection);
        $this->getEntityManager()->flush();
        $this->getService(AuditableListener::class)->flushChanges();

        $id = $vpnConnection->getId();

        $change = $this->findChange(VpnConnection::class, $id);
        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
            'id' => $id,
            'connectionFirewallRules' => $rules,
        ]);

        $newRules = [
            'a' => 'b',
            'b' => [4, 3],
            'c' => [
                'c3' => 'c4',
                'c1' => 'c2',
            ],
            'd' => 5.1,
        ];

        $vpnConnection->setConnectionFirewallRules($newRules);

        $this->getEntityManager()->persist($vpnConnection);
        $this->getEntityManager()->flush();
        $this->getService(AuditableListener::class)->flushChanges();

        $change = $this->findChange(VpnConnection::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'connectionFirewallRules' => $rules,
        ], [
            'id' => $id,
            'connectionFirewallRules' => $newRules,
        ]);

        $this->getEntityManager()->remove($vpnConnection);
        $this->getEntityManager()->flush();
        $this->getService(AuditableListener::class)->flushChanges();

        $change = $this->findChange(VpnConnection::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'connectionFirewallRules' => $newRules,
        ]);
    }

    /**
     * OneToMany is not directly auditable.
     * When entities on both sides of relation are auditable and they can be changed using a single form it should be tested.
     */
    public function testOneToMany()
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => 'TK800',
        ]);

        // Create
        $serialNumber = 'SN123-testOneToMany';
        $name = 'Name-testOneToMany';
        $variable1 = [
            'name' => 'var1',
            'variableValue' => 'Value 1',
        ];
        $variable2 = [
            'name' => 'var2',
            'variableValue' => 'Value 2',
        ];

        $this->jsonPost('/web/api/device/create', [
            'serialNumber' => $serialNumber,
            'name' => $name,
            'deviceType' => $deviceType->getId(),
            'virtualSubnetCidr' => 30,
            'variables' => [
                $variable1,
                $variable2,
            ],
        ]);
        $this->assertResponseIsSuccessfulJson();

        $id = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($id);

        $change = $this->findChange(Device::class, $id);
        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
            'id' => $id,
            'serialNumber' => $serialNumber,
            'name' => $name,
        ]);
        $newValues = $this->decode($change->getNewValues());
        $this->assertArrayNotHasKey('variables', $newValues);

        $changes = $this->findChanges(DeviceVariable::class, 2);
        $this->assertCount(2, $changes);

        $this->assertChange($changes[0], AuditLogChangeType::CREATE, null, [
            'device' => $id,
            'name' => $variable2['name'],
            'variableValue' => $variable2['variableValue'],
        ]);
        $this->assertChange($changes[1], AuditLogChangeType::CREATE, null, [
            'device' => $id,
            'name' => $variable1['name'],
            'variableValue' => $variable1['variableValue'],
        ]);

        // Update - Add variable
        $variable3 = [
            'name' => 'var3',
            'variableValue' => 'Value 3',
        ];

        $this->jsonPost('/web/api/device/'.$id, [
            'serialNumber' => $serialNumber,
            'name' => $name,
            'virtualSubnetCidr' => 30,
            'variables' => [
                $variable1,
                $variable2,
                $variable3,
            ],
        ]);
        $this->assertResponseIsSuccessfulJson();

        $changes = $this->findChanges(DeviceVariable::class, 2);
        $this->assertCount(2, $changes);

        $this->assertChange($changes[0], AuditLogChangeType::CREATE, null, [
            'device' => $id,
            'name' => $variable3['name'],
            'variableValue' => $variable3['variableValue'],
        ]);
        // Verify that change before adding $variable3 was a creation of $variable2 (nothing else has been logged)
        $this->assertChange($changes[1], AuditLogChangeType::CREATE, null, [
            'device' => $id,
            'name' => $variable2['name'],
            'variableValue' => $variable2['variableValue'],
        ]);

        // Update - Edit variable
        $newVariable2 = [
            'name' => 'newVar2',
            'variableValue' => 'New value 2',
        ];
        $this->jsonPost('/web/api/device/'.$id, [
            'serialNumber' => $serialNumber,
            'name' => $name,
            'virtualSubnetCidr' => 30,
            'variables' => [
                $variable1,
                $newVariable2,
                $variable3,
            ],
        ]);
        $this->assertResponseIsSuccessfulJson();

        $changes = $this->findChanges(DeviceVariable::class, 2);
        $this->assertCount(2, $changes);

        $this->assertChange($changes[0], AuditLogChangeType::UPDATE, [
            'device' => $id,
            'name' => $variable2['name'],
            'variableValue' => $variable2['variableValue'],
        ], [
            'device' => $id,
            'name' => $newVariable2['name'],
            'variableValue' => $newVariable2['variableValue'],
        ]);
        // Verify that change before updating $variable2 was a creation of $variable3 (nothing else has been logged)
        $this->assertChange($changes[1], AuditLogChangeType::CREATE, null, [
            'device' => $id,
            'name' => $variable3['name'],
            'variableValue' => $variable3['variableValue'],
        ]);

        // Update - Delete variable
        $this->jsonPost('/web/api/device/'.$id, [
            'serialNumber' => $serialNumber,
            'name' => $name,
            'virtualSubnetCidr' => 30,
            'variables' => [
                $variable1,
                $newVariable2,
            ],
        ]);
        $this->assertResponseIsSuccessfulJson();

        $changes = $this->findChanges(DeviceVariable::class, 2);
        $this->assertCount(2, $changes);

        $this->assertChange($changes[0], AuditLogChangeType::DELETE, [
            'device' => $id,
            'name' => $variable3['name'],
            'variableValue' => $variable3['variableValue'],
        ]);
        // Verify that change before deleting $variable3 was a edit of $variable2
        $this->assertChange($changes[1], AuditLogChangeType::UPDATE, [
            'device' => $id,
            'name' => $variable2['name'],
            'variableValue' => $variable2['variableValue'],
        ], [
            'device' => $id,
            'name' => $newVariable2['name'],
            'variableValue' => $newVariable2['variableValue'],
        ]);

        // Delete
        $this->jsonDelete('/web/api/device/'.$id);
        $this->assertResponseIsSuccessful();

        $change = $this->findChange(Device::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'serialNumber' => $serialNumber,
            'name' => $name,
        ]);
        $oldValues = $this->decode($change->getOldValues());
        $this->assertArrayNotHasKey('variables', $oldValues);

        $changes = $this->findChanges(DeviceVariable::class, 3);
        $this->assertCount(3, $changes);

        $this->assertChange($changes[0], AuditLogChangeType::DELETE, [
            'device' => $id,
            'name' => $newVariable2['name'],
            'variableValue' => $newVariable2['variableValue'],
        ]);
        $this->assertChange($changes[1], AuditLogChangeType::DELETE, [
            'device' => $id,
            'name' => $variable1['name'],
            'variableValue' => $variable1['variableValue'],
        ]);
        // Verify that change before deleting device was a delete of $variable3
        $this->assertChange($changes[2], AuditLogChangeType::DELETE, [
            'device' => $id,
            'name' => $variable3['name'],
            'variableValue' => $variable3['variableValue'],
        ]);
    }

    public function testOneToManyDeleteSetNull()
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => 'TK800',
        ]);

        // Create
        $serialNumber = 'SN123-testOneToManyDeleteSetNull';
        $name = 'Name-testOneToManyDeleteSetNull';

        $this->jsonPost('/web/api/device/create', [
            'serialNumber' => $serialNumber,
            'name' => $name,
            'deviceType' => $deviceType->getId(),
            'virtualSubnetCidr' => 30,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $id = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($id);

        // Add VPN log manually
        $device = $this->getRepository(Device::class)->find($id);

        $vpnLog = new VpnLog();
        $vpnLog->setDevice($device);
        $vpnLog->setMessage('Example message');
        $vpnLog->setLogLevel(LogLevel::INFO);

        $this->getEntityManager()->persist($vpnLog);
        $this->getEntityManager()->flush();

        // Delete
        $this->jsonDelete('/web/api/device/'.$id);
        $this->assertResponseIsSuccessful();
    }

    public function testManyToMany()
    {
        $accessTag1 = new AccessTag();
        $accessTag1->setName('Access tag 1 testManyToMany');
        $this->getEntityManager()->persist($accessTag1);

        $accessTag2 = new AccessTag();
        $accessTag2->setName('Access tag 2 testManyToMany');
        $this->getEntityManager()->persist($accessTag2);

        $accessTag3 = new AccessTag();
        $accessTag3->setName('Access tag 3 testManyToMany');
        $this->getEntityManager()->persist($accessTag3);

        $this->getEntityManager()->flush();

        $this->loginApi('admin', 'admin');

        // Create
        $username = 'smartems';
        $accessTags = [
            $accessTag1->getId(),
            $accessTag2->getId(),
        ];

        $this->jsonPost('/web/api/user/create', [
            'username' => $username,
            'plainPassword' => '12345678',
            'plainPasswordRepeat' => '12345678',
            'roleSmartems' => true,
            'accessTags' => $accessTags,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $id = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($id);

        $change = $this->findChange(User::class, $id);
        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
            'id' => $id,
            'username' => $username,
            'accessTags' => $accessTags,
        ]);

        // Update - Add access tag
        $newAccessTags = [
            $accessTag1->getId(),
            $accessTag2->getId(),
            $accessTag3->getId(),
        ];
        $this->jsonPost('/web/api/user/'.$id, [
            'username' => $username,
            'roleSmartems' => true,
            'accessTags' => $newAccessTags,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(User::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'username' => $username,
            'accessTags' => $accessTags,
        ], [
            'id' => $id,
            'username' => $username,
            'accessTags' => $newAccessTags,
        ]);

        // Update - Remove access tag
        $accessTags = $newAccessTags;
        $newAccessTags = [
            $accessTag1->getId(),
            $accessTag3->getId(),
        ];
        $this->jsonPost('/web/api/user/'.$id, [
            'username' => $username,
            'roleSmartems' => true,
            'accessTags' => $newAccessTags,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(User::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'username' => $username,
            'accessTags' => $accessTags,
        ], [
            'id' => $id,
            'username' => $username,
            'accessTags' => $newAccessTags,
        ]);

        // Delete
        $this->jsonDelete('/web/api/user/'.$id);
        $this->assertResponseIsSuccessful();

        $change = $this->findChange(User::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'username' => $username,
            'accessTags' => $newAccessTags,
        ]);
    }

    public function testManyToOne()
    {
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => 'TK800',
        ]);

        $template1 = new Template();
        $template1->setName('Template 1');
        $template1->setDeviceType($deviceType);
        $this->getEntityManager()->persist($template1);

        $template2 = new Template();
        $template2->setName('Template 2');
        $template2->setDeviceType($deviceType);
        $this->getEntityManager()->persist($template2);

        $this->getEntityManager()->flush();

        $this->loginApi('admin', 'admin');

        // Create
        $serialNumber = 'SN123-testManyToOne';
        $name = 'Name-testManyToOne';
        $template = $template1->getId();

        $this->jsonPost('/web/api/device/create', [
            'serialNumber' => $serialNumber,
            'name' => $name,
            'deviceType' => $deviceType->getId(),
            'virtualSubnetCidr' => 30,
            'template' => $template,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $id = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($id);

        $change = $this->findChange(Device::class, $id);
        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
            'id' => $id,
            'template' => $template,
        ]);

        // Update
        $newTemplate = $template2->getId();
        $this->jsonPost('/web/api/device/'.$id, [
            'serialNumber' => $serialNumber,
            'name' => $name,
            'virtualSubnetCidr' => 30,
            'template' => $newTemplate,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(Device::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'template' => $template,
        ], [
            'id' => $id,
            'template' => $newTemplate,
        ]);

        // Delete
        $this->jsonDelete('/web/api/device/'.$id);
        $this->assertResponseIsSuccessful();

        $change = $this->findChange(Device::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'template' => $newTemplate,
        ]);
    }

    /**
     * Test collection that have ManyToMany field inside. This should be treated as seperate case due to Symfony collection being involved.
     */
    public function testCollectionManyToMany()
    {
        $accessTag1 = new AccessTag();
        $accessTag1->setName('Access tag 1 testCollectionManyToMany');
        $this->getEntityManager()->persist($accessTag1);

        $accessTag2 = new AccessTag();
        $accessTag2->setName('Access tag 2 testCollectionManyToMany');
        $this->getEntityManager()->persist($accessTag2);

        $accessTag3 = new AccessTag();
        $accessTag3->setName('Access tag 3 testCollectionManyToMany');
        $this->getEntityManager()->persist($accessTag3);

        $this->getEntityManager()->flush();

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => 'TK800',
        ]);

        $this->loginApi('admin', 'admin');

        // Create
        $accessTags = [
            $accessTag1->getId(),
            $accessTag2->getId(),
        ];

        $this->jsonPost('/web/api/device/create', [
            'name' => 'Name-testCollectionManyToMany',
            'serialNumber' => 'SN123-testCollectionManyToMany',
            'deviceType' => $deviceType->getId(),
            'virtualSubnetCidr' => 29,
            'endpointDevices' => [
                [
                    'name' => 'Example endpoint device 1',
                    'physicalIp' => '1.1.1.1',
                    'virtualIpHostPart' => 1,
                    'accessTags' => $accessTags,
                ],
            ],
        ]);
        $this->assertResponseIsSuccessfulJson();

        $deviceId = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($deviceId);

        $endpointDevice = $this->getRepository(DeviceEndpointDevice::class)->findOneBy([
            'device' => $deviceId,
        ]);
        $this->assertInstanceOf(DeviceEndpointDevice::class, $endpointDevice);
        $id = $endpointDevice->getId();

        $change = $this->findChange(DeviceEndpointDevice::class, $id);
        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
            'id' => $id,
            'accessTags' => $accessTags,
        ]);

        // Update - Add access tag
        $newAccessTags = [
            $accessTag1->getId(),
            $accessTag2->getId(),
            $accessTag3->getId(),
        ];
        $this->jsonPost('/web/api/device/'.$deviceId, [
            'name' => 'Name-testCollectionManyToMany',
            'serialNumber' => 'SN123-testCollectionManyToMany',
            'virtualSubnetCidr' => 29,
            'endpointDevices' => [
                [
                    'name' => 'Example endpoint device 1',
                    'physicalIp' => '1.1.1.1',
                    'virtualIpHostPart' => 1,
                    'accessTags' => $newAccessTags,
                ],
            ],
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(DeviceEndpointDevice::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'accessTags' => $accessTags,
        ], [
            'id' => $id,
            'accessTags' => $newAccessTags,
        ]);

        // Update - Remove access tag
        $accessTags = $newAccessTags;
        $newAccessTags = [
            $accessTag1->getId(),
            $accessTag3->getId(),
        ];
        $this->jsonPost('/web/api/device/'.$deviceId, [
            'name' => 'Name-testCollectionManyToMany',
            'serialNumber' => 'SN123-testCollectionManyToMany',
            'virtualSubnetCidr' => 29,
            'endpointDevices' => [
                [
                    'name' => 'Example endpoint device 1',
                    'physicalIp' => '1.1.1.1',
                    'virtualIpHostPart' => 1,
                    'accessTags' => $newAccessTags,
                ],
            ],
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(DeviceEndpointDevice::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'accessTags' => $accessTags,
        ], [
            'id' => $id,
            'accessTags' => $newAccessTags,
        ]);

        // Delete
        $this->jsonDelete('/web/api/device/'.$deviceId);
        $this->assertResponseIsSuccessful();

        $change = $this->findChange(DeviceEndpointDevice::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'accessTags' => $newAccessTags,
        ]);
    }

    public function testBigintWebApi()
    {
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => 'TK800',
        ]);

        $this->loginApi('admin', 'admin');

        // Create
        $physicalIp = '1.1.1.1';
        $physicalIpSortable = ip2long($physicalIp);

        $this->jsonPost('/web/api/device/create', [
            'name' => 'Name-testBigintWebApi',
            'serialNumber' => 'SN123-testBigintWebApi',
            'deviceType' => $deviceType->getId(),
            'virtualSubnetCidr' => 29,
            'endpointDevices' => [
                [
                    'name' => 'Example endpoint device 1',
                    'physicalIp' => $physicalIp,
                    'virtualIpHostPart' => 1,
                ],
            ],
        ]);
        $this->assertResponseIsSuccessfulJson();

        $deviceId = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($deviceId);

        $endpointDevice = $this->getRepository(DeviceEndpointDevice::class)->findOneBy([
            'device' => $deviceId,
        ]);
        $this->assertInstanceOf(DeviceEndpointDevice::class, $endpointDevice);
        $id = $endpointDevice->getId();

        $change = $this->findChange(DeviceEndpointDevice::class, $id);
        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
            'id' => $id,
            'physicalIp' => $physicalIp,
            'physicalIpSortable' => $physicalIpSortable,
        ]);

        // Update
        $newPhysicalIp = '255.255.255.255';
        $newPhysicalIpSortable = ip2long($newPhysicalIp);
        $this->jsonPost('/web/api/deviceendpointdevice/'.$id, [
            'name' => 'Example endpoint device 1',
            'physicalIp' => $newPhysicalIp,
            'virtualIpHostPart' => 1,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(DeviceEndpointDevice::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'physicalIp' => $physicalIp,
            'physicalIpSortable' => $physicalIpSortable,
        ], [
            'id' => $id,
            'physicalIp' => $newPhysicalIp,
            'physicalIpSortable' => $newPhysicalIpSortable,
        ]);

        // Delete
        $this->jsonDelete('/web/api/deviceendpointdevice/'.$id);
        $this->assertResponseIsSuccessful();

        $change = $this->findChange(DeviceEndpointDevice::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'physicalIp' => $newPhysicalIp,
            'physicalIpSortable' => $newPhysicalIpSortable,
        ]);
    }

    /**
     * Cannot test create with communication.
     */
    public function testBigintCommunication()
    {
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => 'TK800',
        ]);

        $this->loginApi('admin', 'admin');

        $serialNumber = 'SN123-testBigintCommunication';

        $this->jsonPost('/web/api/device/create', [
            'name' => 'Name-testBigintCommunication',
            'serialNumber' => $serialNumber,
            'enabled' => true,
            'deviceType' => $deviceType->getId(),
            'virtualSubnetCidr' => 30,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $id = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($id);

        $this->loginDigestAuth('router', '123456');

        // Update - Small value
        $cellularUptime1 = '2day,3:34:21';
        $this->post('/router/tk800/config', [
            'Serial' => $serialNumber,
            'Firmware' => 'v1.0.0',
            'Cellular1_uptime' => $cellularUptime1,
        ]);
        $this->assertResponseIsSuccessful();

        // We expect empty response as router has no template
        $this->assertSame('', $this->getResponseContent());

        $change = $this->findChange(Device::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'cellularUptime1' => null,
            'cellularUptimeSeconds1' => null,
        ], [
            'cellularUptime1' => $cellularUptime1,
            'cellularUptimeSeconds1' => UptimeConverter::convertToSeconds($cellularUptime1),
        ]);

        // Update - Big value
        $bigintDays = 320000; // More than 2,147,483,647 in seconds which is MySQL INT max value
        $newCellularUptime1 = $bigintDays.'day,3:34:21';
        $this->post('/router/tk800/config', [
            'Serial' => $serialNumber,
            'Firmware' => 'v1.0.0',
            'Cellular1_uptime' => $newCellularUptime1,
        ]);
        $this->assertResponseIsSuccessful();

        // We expect empty response as router has no template
        $this->assertSame('', $this->getResponseContent());

        $change = $this->findChange(Device::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'cellularUptime1' => $cellularUptime1,
            'cellularUptimeSeconds1' => UptimeConverter::convertToSeconds($cellularUptime1),
        ], [
            'cellularUptimeSeconds1' => $newCellularUptime1,
            'cellularUptimeSeconds1' => UptimeConverter::convertToSeconds($newCellularUptime1),
        ]);

        // Delete
        $this->loginApi('admin', 'admin');

        $this->jsonDelete('/web/api/device/'.$id);
        $this->assertResponseIsSuccessful();

        $change = $this->findChange(Device::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'cellularUptimeSeconds1' => $newCellularUptime1,
            'cellularUptimeSeconds1' => UptimeConverter::convertToSeconds($newCellularUptime1),
        ]);
    }

    public function testEnum()
    {
        $configuration = $this->getConfiguration();
        $configuration->setConfigGeneratorPhp(true);

        $this->getEntityManager()->persist($configuration);
        $this->getEntityManager()->flush();

        $this->getConfigurationManager()->refreshConfiguration();

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => 'TK800',
        ]);

        $this->loginApi('admin', 'admin');

        // Create
        $generator = ConfigGenerator::TWIG->value;
        $this->jsonPost('/web/api/config/create', [
            'deviceType' => $deviceType->getId(),
            'feature' => Feature::PRIMARY->value,
            'name' => 'Example config 1',
            'generator' => $generator,
            'content' => 'Example content 1',
        ]);
        $this->assertResponseIsSuccessfulJson();

        $id = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($id);

        $change = $this->findChange(Config::class, $id);
        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
            'id' => $id,
            'generator' => $generator,
        ]);

        // Update
        $newGenerator = ConfigGenerator::PHP->value;
        $this->jsonPost('/web/api/config/'.$id, [
            'name' => 'Example config 1',
            'generator' => $newGenerator,
            'content' => 'Example content 1',
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(Config::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'generator' => $generator,
        ], [
            'id' => $id,
            'generator' => $newGenerator,
        ]);

        // Delete
        $this->jsonDelete('/web/api/config/'.$id);
        $this->assertResponseIsSuccessful();

        $change = $this->findChange(Config::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'generator' => $newGenerator,
        ]);
    }

    public function testText()
    {
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => 'TK800',
        ]);

        $this->loginApi('admin', 'admin');

        // Create
        $content = 'Example content 1';
        $this->jsonPost('/web/api/config/create', [
            'deviceType' => $deviceType->getId(),
            'feature' => Feature::PRIMARY->value,
            'name' => 'Example config 1',
            'generator' => ConfigGenerator::TWIG->value,
            'content' => $content,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $id = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($id);

        $change = $this->findChange(Config::class, $id);
        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
            'id' => $id,
            'content' => $content,
        ]);

        // Update
        $newContent = 'Example content 2';
        $this->jsonPost('/web/api/config/'.$id, [
            'name' => 'Example config 1',
            'generator' => ConfigGenerator::TWIG->value,
            'content' => $newContent,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(Config::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'content' => $content,
        ], [
            'id' => $id,
            'content' => $newContent,
        ]);

        // Delete
        $this->jsonDelete('/web/api/config/'.$id);
        $this->assertResponseIsSuccessful();

        $change = $this->findChange(Config::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'content' => $newContent,
        ]);
    }

    public function testDatetime()
    {
        $this->loginApi('admin', 'admin');

        // Create
        $username = 'newAdmin';
        $enabledExpireAt = (new \DateTime('2023-11-01 12:11:05', new \DateTimeZone('UTC')))->format('c');

        $this->jsonPost('/web/api/user/create', [
            'username' => $username,
            'plainPassword' => '12345678',
            'plainPasswordRepeat' => '12345678',
            'roleAdmin' => true,
            'enabled' => true,
            'enabledExpireAt' => $enabledExpireAt,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $id = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($id);

        $change = $this->findChange(User::class, $id);
        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
            'id' => $id,
            'enabledExpireAt' => $enabledExpireAt,
        ]);

        // Update
        $newEnabledExpireAt = (new \DateTime('2024-02-05 09:05:55', new \DateTimeZone('UTC')))->format('c');
        $this->jsonPost('/web/api/user/'.$id, [
            'username' => $username,
            'roleAdmin' => true,
            'enabled' => true,
            'enabledExpireAt' => $newEnabledExpireAt,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(User::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'enabledExpireAt' => $enabledExpireAt,
        ], [
            'id' => $id,
            'enabledExpireAt' => $newEnabledExpireAt,
        ]);

        // Delete
        $this->jsonDelete('/web/api/user/'.$id);
        $this->assertResponseIsSuccessful();

        $change = $this->findChange(User::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'enabledExpireAt' => $newEnabledExpireAt,
        ]);
    }

    public function testEncrypted()
    {
        $this->loginApi('admin', 'admin');

        // Create
        $backupPassword = '123456';
        $this->jsonPost('/web/api/maintenanceschedule/create', [
            'name' => 'Name',
            'backupDatabase' => true,
            'backupPassword' => $backupPassword,
            'dayOfMonth' => -1,
            'dayOfWeek' => -1,
            'hour' => -1,
            'minute' => -1,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $id = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($id);

        $change = $this->findChange(MaintenanceSchedule::class, $id);
        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
            'id' => $id,
            'backupPassword' => AuditableInterface::VALUE_ENCRYPTED_UNCHANGED,
        ]);

        // Update with encrypted
        $newBackupPassword = '654321';
        $this->jsonPost('/web/api/maintenanceschedule/'.$id, [
            'name' => 'Name 1',
            'backupDatabase' => true,
            'backupPassword' => $newBackupPassword,
            'dayOfMonth' => -1,
            'dayOfWeek' => -1,
            'hour' => -1,
            'minute' => -1,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(MaintenanceSchedule::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'backupPassword' => AuditableInterface::VALUE_ENCRYPTED_CHANGED,
        ], [
            'id' => $id,
            'backupPassword' => AuditableInterface::VALUE_ENCRYPTED_CHANGED,
        ]);

        // Update without encrypted
        $newBackupPassword = '654321';
        $this->jsonPost('/web/api/maintenanceschedule/'.$id, [
            'name' => 'Name 2',
            'backupDatabase' => true,
            'backupPassword' => $newBackupPassword,
            'dayOfMonth' => -1,
            'dayOfWeek' => -1,
            'hour' => -1,
            'minute' => -1,
        ]);
        $this->assertResponseIsSuccessfulJson();

        $change = $this->findChange(MaintenanceSchedule::class, $id);
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'id' => $id,
            'backupPassword' => AuditableInterface::VALUE_ENCRYPTED_UNCHANGED,
        ], [
            'id' => $id,
            'backupPassword' => AuditableInterface::VALUE_ENCRYPTED_UNCHANGED,
        ]);

        // Delete
        $this->jsonDelete('/web/api/maintenanceschedule/'.$id);
        $this->assertResponseIsSuccessful();

        $change = $this->findChange(MaintenanceSchedule::class, $id);
        $this->assertChange($change, AuditLogChangeType::DELETE, [
            'id' => $id,
            'backupPassword' => AuditableInterface::VALUE_ENCRYPTED_UNCHANGED,
        ]);
    }
}
