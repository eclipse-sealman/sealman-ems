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

namespace Tests\DataFixtures\DeviceCommunication;

use App\DataFixtures\AbstractFixtureGroupAsClass;
use App\DataFixtures as ProdFixtures;
use App\Entity\CertificateType;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\User;
use App\Entity\UserDeviceType;
use App\Enum\AuthenticationMethod;
use App\Enum\CertificateBehavior;
use App\Enum\CertificateCategory;
use App\Enum\CertificateEntity;
use App\Enum\CommunicationProcedure;
use App\Enum\ConfigFormat;
use App\Enum\DeviceTypeIcon;
use App\Enum\FieldRequirement;
use App\Enum\PkiType;
use App\Enum\UserRole;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DeviceAuthenticationFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    use DeviceCommunicationFactoryTrait;

    /**
     * Password hasher for creating device authentication users.
     *
     * @var UserPasswordHasherInterface
     */
    protected $hasher;

    /**
     * DeviceAuthenticationFixtures constructor.
     */
    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    /**
     * Loads test device authentication fixtures into the database.
     */
    public function load(ObjectManager $manager): void
    {
        // Add a test device authentication user for Flex edge device
        $this->addDeviceAuthenticationUser($manager, 'flexedge', '123456', ['Flex edge device']);

        // Create an alternative device type to simulate an incorrectly configured device
        $deviceType = new DeviceType();
        $deviceType->setName('TK800INVALID');
        $deviceType->setDeviceName('Router');
        $deviceType->setCertificateCommonNamePrefix(\substr('RI1', 0, 6));
        $deviceType->setIcon(DeviceTypeIcon::ROUTER);
        $deviceType->setColor('#00FF00');
        $deviceType->setHasFirmware1(true);
        $deviceType->setNameFirmware1('Firmware');
        $deviceType->setHasConfig1(true);
        $deviceType->setNameConfig1('Startup config');
        $deviceType->setHasConfig2(true);
        $deviceType->setNameConfig2('Running config');
        $deviceType->setHasAlwaysReinstallConfig2(true);
        $deviceType->setAuthenticationMethod(AuthenticationMethod::NONE);
        $deviceType->setRoutePrefix('/router/tk800invalid');
        $deviceType->setCommunicationProcedure(CommunicationProcedure::ROUTER);
        $deviceType->setHasCertificates(true);
        $deviceType->setHasVpn(true);
        $deviceType->setHasEndpointDevices(true);
        $deviceType->setHasTemplates(true);
        $deviceType->setHasVariables(true);
        $deviceType->setHasGsm(true);
        $deviceType->setHasRequestDiagnose(true);
        $deviceType->setFieldSerialNumber(FieldRequirement::REQUIRED);
        $deviceType->setFieldImsi(FieldRequirement::OPTIONAL);
        $deviceType->setFieldModel(FieldRequirement::OPTIONAL);
        $deviceType->setEnableConfigMinRsrp(true);
        $deviceType->setConfigMinRsrp(-113);
        $deviceType->setEnableFirmwareMinRsrp(true);
        $deviceType->setFirmwareMinRsrp(-113);
        $deviceType->setEnableConnectionAggregation(true);
        $deviceType->setFormatConfig1(ConfigFormat::PLAIN);
        $deviceType->setFormatConfig2(ConfigFormat::PLAIN);

        $manager->persist($deviceType);

        $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($deviceType);

        // Create an alternative device instance for the invalid device type
        $device = new Device();
        $device->setDeviceType($deviceType);
        $device->setName('tk800invalid');
        $device->setSerialNumber('tk800invalid');
        $device->setIdentifier($communicationProcedure->generateIdentifier($device));
        $device->setUuid($communicationProcedure->getDeviceTypeUniqueUuid());
        $device->setHashIdentifier($communicationProcedure->getDeviceUniqueHashIdentifier());
        $device->setVirtualSubnetCidr($deviceType->getVirtualSubnetCidr());
        $device->setMasqueradeType($deviceType->getMasqueradeType());
        $manager->persist($device);

        // Create an alternative certificate type for authentication
        $authenticationCertificateType = new CertificateType();

        $authenticationCertificateType->setName('Authentication');
        $authenticationCertificateType->setCommonNamePrefix('a');
        $authenticationCertificateType->setVariablePrefix('a');
        $authenticationCertificateType->setEnabled(true);
        $authenticationCertificateType->setDownloadEnabled(true);
        $authenticationCertificateType->setUploadEnabled(true);
        $authenticationCertificateType->setDeleteEnabled(true);
        $authenticationCertificateType->setPkiEnabled(false);
        $authenticationCertificateType->setEnabledBehaviour(CertificateBehavior::NONE);
        $authenticationCertificateType->setDisabledBehaviour(CertificateBehavior::NONE);
        $authenticationCertificateType->setPkiType(PkiType::NONE);
        $authenticationCertificateType->setCertificateCategory(CertificateCategory::CUSTOM);
        $authenticationCertificateType->setCertificateEntity(CertificateEntity::DEVICE);
        $manager->persist($authenticationCertificateType);

        $manager->flush();
    }

    /**
     * Adds a device authentication user and assigns device types.
     */
    protected function addDeviceAuthenticationUser(ObjectManager $manager, string $userName, string $password, array $deviceTypeNames): void
    {
        $user = new User();
        $user->setUsername($userName);
        $user->setRoleDevice(true);
        $user->setEnabled(true);
        $user->setSalt(rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '='));
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $manager->persist($user);

        // Assign each device type to the user
        foreach ($deviceTypeNames as $deviceTypeName) {
            $userDeviceType = new UserDeviceType();
            $userDeviceType->setDeviceType($manager->getRepository(DeviceType::class)->findOneBy(['name' => $deviceTypeName]));
            $userDeviceType->setUser($user);
            $userDeviceType->setUserRole(UserRole::DEVICE);
            $manager->persist($userDeviceType);
        }
    }

    /**
     * Returns the list of fixture classes this fixture depends on.
     */
    public function getDependencies(): array
    {
        return [
            ProdFixtures\UserFixtures::class,
            ProdFixtures\DeviceTypeFixtures::class,
        ];
    }
}
