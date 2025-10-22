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

namespace App\Controller\Api;

use App\Attribute\Areas;
use App\Entity\AccessTag;
use App\Entity\Config;
use App\Entity\Configuration;
use App\Entity\Device;
use App\Entity\DeviceEndpointDevice;
use App\Entity\DeviceType;
use App\Entity\DeviceTypeSecret;
use App\Entity\Firmware;
use App\Entity\Label;
use App\Entity\MaintenanceSchedule;
use App\Entity\Template;
use App\Entity\User;
use App\Enum\CommunicationProcedure;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Doctrine\Common\Collections\Collection;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Rest\Route('/options')]
#[Rest\View(serializerGroups: ['identification', 'deviceType:identification'])]
#[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS') or is_granted('ROLE_VPN')")]
#[Areas(['admin', 'smartems', 'vpnsecuritysuite'])]
#[OA\Tag('Options')]
class OptionsController extends AbstractApiController
{
    use ConfigurationManagerTrait;
    use DeviceCommunicationFactoryTrait;
    use SecurityHelperTrait;

    #[Rest\Get('/masquerade/default/subnets')]
    #[Rest\View(serializerGroups: ['options:masqueradeDefaultSubnets'])]
    #[Api\Summary('Get default masquerade subnets')]
    #[Api\Response200Groups(description: 'Default masquerade subnets', content: new NA\Model(type: Configuration::class))]
    // Set to admin because it is required to edit deviceTypes
    #[Security("is_granted('ROLE_ADMIN')")]
    #[Areas(['admin'])]
    public function masqueradeDefaultSubnetsAction()
    {
        return $this->getConfiguration();
    }

    #[Rest\Get('/users')]
    #[Api\Summary('Get users')]
    #[Api\Response200ArraySubjectGroups(User::class)]
    #[Security("is_granted('ROLE_ADMIN')")]
    #[Areas(['admin'])]
    public function usersAction()
    {
        // Event without VPN license all users should be visible
        return $this->getRepository(User::class)->findAll();
    }

    #[Rest\Get('/devices')]
    #[Api\Summary('Get devices')]
    #[Api\Response200ArraySubjectGroups(Device::class)]
    public function devicesAction()
    {
        $queryBuilder = $this->getRepository(Device::class)->createQueryBuilder('d');

        $accessTagsQueryApendix = '';

        if (!$this->isAllDevicesGranted() && $this->isGranted('ROLE_VPN')) {
            $queryBuilder->leftJoin('d.endpointDevices', 'ed');
            $accessTagsQueryApendix = 'OR :accessTags MEMBER OF ed.accessTags';
        }

        $this->applyUserAccessTagsQueryModification($queryBuilder, 'd', $accessTagsQueryApendix);

        return $queryBuilder->getQuery()->getResult();
    }

    #[Rest\Get('/templates')]
    #[Api\Summary('Get templates')]
    #[Api\Response200ArraySubjectGroups(Template::class)]
    public function templatesAction()
    {
        $queryBuilder = $this->getRepository(Template::class)->createQueryBuilder('t');

        if (!$this->isAllDevicesGranted()) {
            $queryBuilder->leftJoin('t.devices', 'd');

            $this->applyUserAccessTagsQueryModification($queryBuilder, 'd', ' OR t.createdBy = :user');

            $queryBuilder->setParameter('user', $this->getUser());
        }

        return $queryBuilder->getQuery()->getResult();
    }

    #[Rest\Get('/templates/{deviceTypeId}')]
    #[Api\Summary('Get templates by device type ID')]
    #[Api\Parameter(name: 'deviceTypeId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of device type')]
    #[Api\Response200ArraySubjectGroups(Template::class)]
    public function deviceTypeTemplatesAction(int $deviceTypeId)
    {
        $queryBuilder = $this->getRepository(Template::class)->createQueryBuilder('t');
        $queryBuilder->andWhere('t.deviceType = :deviceType');
        $queryBuilder->setParameter('deviceType', $deviceTypeId);

        if (!$this->isAllDevicesGranted()) {
            $queryBuilder->leftJoin('t.devices', 'd');

            $this->applyUserAccessTagsQueryModification($queryBuilder, 'd', ' OR t.createdBy = :user');

            $queryBuilder->setParameter('user', $this->getUser());
        }

        return $queryBuilder->getQuery()->getResult();
    }

    #[Rest\Get('/devicetypesecrets/{deviceTypeId}')]
    #[Api\Summary('Get secrets by device type ID')]
    #[Api\Parameter(name: 'deviceTypeId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of device type')]
    #[Api\Response200ArraySubjectGroups(DeviceTypeSecret::class)]
    // Set to admin because it is required to edit deviceTypes
    #[Security("is_granted('ROLE_ADMIN')")]
    #[Areas(['admin'])]
    public function deviceTypeSecretsAction(int $deviceTypeId)
    {
        $queryBuilder = $this->getRepository(DeviceTypeSecret::class)->createQueryBuilder('dts');
        $queryBuilder->andWhere('dts.deviceType = :deviceType');
        $queryBuilder->setParameter('deviceType', $deviceTypeId);

        return $queryBuilder->getQuery()->getResult();
    }

    #[Rest\Get('/access/tags')]
    #[Api\Summary('Get access tags')]
    #[Api\Response200ArraySubjectGroups(AccessTag::class)]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS') or is_granted('ROLE_VPN_ENDPOINTDEVICES')")]
    #[Areas(['admin', 'smartems'])]
    public function accessTagsAction()
    {
        $queryBuilder = $this->getRepository(AccessTag::class)->createQueryBuilder('at');

        $this->applyUserAccessTagsQueryModificationForAccessTags($queryBuilder, 'at');

        return $queryBuilder->getQuery()->getResult();
    }

    #[Rest\Get('/labels')]
    #[Api\Summary('Get labels')]
    #[Api\Response200ArraySubjectGroups(Label::class)]
    public function labelsAction()
    {
        return $this->getRepository(Label::class)->findAll();
    }

    #[Rest\Get('/configs/{feature}/{deviceTypeId}')]
    #[Api\Summary('Get configs by feature and device type ID')]
    #[Api\Parameter(name: 'feature', in: 'path', schema: new OA\Schema(type: 'string'), description: 'Feature')]
    #[Api\Parameter(name: 'deviceTypeId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of device type')]
    #[Api\Response200ArraySubjectGroups(Config::class)]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function configsAction(string $feature, int $deviceTypeId)
    {
        $queryBuilder = $this->getRepository(Config::class)->createQueryBuilder('c');
        $queryBuilder->andWhere('c.feature = :feature');
        $queryBuilder->setParameter('feature', $feature);
        $queryBuilder->andWhere('c.deviceType = :deviceType');
        $queryBuilder->setParameter('deviceType', $deviceTypeId);

        $this->applyUserAccessTagsQueryModificationForTemplateComponents($queryBuilder, 'c');

        return $queryBuilder->getQuery()->getResult();
    }

    #[Rest\Get('/firmwares/{feature}/{deviceTypeId}')]
    #[Api\Summary('Get firmwares by feature and device type ID')]
    #[Api\Parameter(name: 'feature', in: 'path', schema: new OA\Schema(type: 'string'), description: 'Feature')]
    #[Api\Parameter(name: 'deviceTypeId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of device type')]
    #[Api\Response200ArraySubjectGroups(Firmware::class)]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function firmwaresAction(string $feature, int $deviceTypeId)
    {
        $queryBuilder = $this->getRepository(Firmware::class)->createQueryBuilder('f');
        $queryBuilder->andWhere('f.feature = :feature');
        $queryBuilder->setParameter('feature', $feature);
        $queryBuilder->andWhere('f.deviceType = :deviceType');
        $queryBuilder->setParameter('deviceType', $deviceTypeId);

        $this->applyUserAccessTagsQueryModificationForTemplateComponents($queryBuilder, 'f');

        return $queryBuilder->getQuery()->getResult();
    }

    #[Rest\Get('/communication/procedures')]
    #[Api\Summary('Get communication procedures')]
    #[Api\Response200(description: 'Communication procedures', content: new OA\JsonContent(
        type: 'array',
        items: new OA\Items(type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'representation', type: 'string'),
        ])),
    )]
    #[Security("is_granted('ROLE_ADMIN')")]
    #[Areas(['admin'])]
    public function communicationProceduresAction()
    {
        $returnArray = [];
        foreach (CommunicationProcedure::cases() as $communicationProcedure) {
            $communicationProcedureService = $this->deviceCommunicationFactory->getDeviceCommunicationByName($communicationProcedure->value);
            if ($communicationProcedureService->isCommunicationProcedureValid()) {
                $returnArray[] = ['id' => $communicationProcedure->value, 'representation' => 'enum.deviceType.communicationProcedure.'.$communicationProcedure->value];
            }
        }

        return $returnArray;
    }

    #[Rest\Get('/devicetype/{id}', requirements: ['id' => '\d+'])]
    #[Rest\View(serializerGroups: ['identification', 'deviceType:public'])]
    #[Api\Summary('Get extended data for available device type by ID')]
    #[Api\ParameterPathId]
    #[Api\Response200Groups(description: 'Available device type extended data', content: new NA\Model(type: DeviceType::class))]
    #[Api\Response404Id('Available device type with specified ID was not found')]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    // Method provides extended deviceType information for Device, Config, Firmware, Template creation
    public function getDeviceTypeAction(int $id)
    {
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['id' => $id, 'enabled' => true]);

        if (!$deviceType) {
            throw new NotFoundHttpException();
        }

        if (!$deviceType->getIsAvailable()) {
            throw new NotFoundHttpException();
        }

        return $deviceType;
    }

    // Endpoint provides only available device types for create tile screen
    #[Rest\Get('/available/device/types')]
    #[Api\Summary('Get available device types')]
    #[Api\Response200ArraySubjectGroups(class: DeviceType::class, description: 'Array of available device types')]
    public function availableDeviceTypesAction()
    {
        return $this->removeDisabledDeviceTypes($this->getRepository(DeviceType::class)->findBy(['enabled' => true]));
    }

    // Endpoint provides only available device types for create tile screen
    #[Rest\Get('/available/template/device/types')]
    #[Api\Summary('Get available device types that supports templates')]
    #[Api\Response200ArraySubjectGroups(class: DeviceType::class, description: 'Array of available device types that supports templates')]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function availableTemplateDeviceTypesAction()
    {
        return $this->removeDisabledDeviceTypes($this->getRepository(DeviceType::class)->findBy(['enabled' => true, 'hasTemplates' => true]));
    }

    // Endpoint provides only available device types for create tile screen
    #[Rest\Get('/available/config/device/types')]
    #[Api\Summary('Get available device types that supports config')]
    #[Api\Response200ArraySubjectGroups(class: DeviceType::class, description: 'Array of available device types that supports config')]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function availableConfigDeviceTypesAction()
    {
        $queryBuilder = $this->getRepository(DeviceType::class)->createQueryBuilder('dt');
        $queryBuilder->andWhere('dt.hasConfig1 = :hasConfig OR dt.hasConfig2 = :hasConfig OR dt.hasConfig3 = :hasConfig');
        $queryBuilder->setParameter('hasConfig', true);
        $queryBuilder->andWhere('dt.enabled = :enabled');
        $queryBuilder->setParameter('enabled', true);

        return $this->removeDisabledDeviceTypes($queryBuilder->getQuery()->getResult());
    }

    // Endpoint provides only available device types for create tile screen
    #[Rest\Get('/available/firmware/device/types')]
    #[Api\Summary('Get available device types that supports firmware')]
    #[Api\Response200ArraySubjectGroups(class: DeviceType::class, description: 'Array of available device types that supports firmware')]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function availableFirmwareDeviceTypesAction()
    {
        $queryBuilder = $this->getRepository(DeviceType::class)->createQueryBuilder('dt');
        $queryBuilder->andWhere('dt.hasFirmware1 = :hasFirmware OR dt.hasFirmware2 = :hasFirmware OR dt.hasFirmware3 = :hasFirmware');
        $queryBuilder->setParameter('hasFirmware', true);
        $queryBuilder->andWhere('dt.enabled = :enabled');
        $queryBuilder->setParameter('enabled', true);

        return $this->removeDisabledDeviceTypes($queryBuilder->getQuery()->getResult());
    }

    #[Rest\Get('/device/types')]
    #[Api\Summary('Get device types')]
    #[Api\Response200ArraySubjectGroups(class: DeviceType::class)]
    public function deviceTypesAction()
    {
        return $this->getRepository(DeviceType::class)->findBy(['enabled' => true]);
    }

    #[Rest\Get('/template/device/types')]
    #[Api\Summary('Get device types that supports templates')]
    #[Api\Response200ArraySubjectGroups(class: DeviceType::class, description: 'Array of device types that supports templates')]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function templateDeviceTypesAction()
    {
        return $this->getRepository(DeviceType::class)->findBy(['enabled' => true, 'hasTemplates' => true]);
    }

    #[Rest\Get('/config/device/types')]
    #[Api\Summary('Get device types that supports configs')]
    #[Api\Response200ArraySubjectGroups(class: DeviceType::class, description: 'Array of device types that supports configs')]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function configDeviceTypesAction()
    {
        $queryBuilder = $this->getRepository(DeviceType::class)->createQueryBuilder('dt');
        $queryBuilder->andWhere('dt.hasConfig1 = :hasConfig OR dt.hasConfig2 = :hasConfig OR dt.hasConfig3 = :hasConfig');
        $queryBuilder->setParameter('hasConfig', true);
        $queryBuilder->andWhere('dt.enabled = :enabled');
        $queryBuilder->setParameter('enabled', true);

        return $queryBuilder->getQuery()->getResult();
    }

    #[Rest\Get('/firmware/device/types')]
    #[Api\Summary('Get device types that supports firmwares')]
    #[Api\Response200ArraySubjectGroups(class: DeviceType::class, description: 'Array of device types that supports firmwares')]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function firmwareDeviceTypesAction()
    {
        $queryBuilder = $this->getRepository(DeviceType::class)->createQueryBuilder('dt');
        $queryBuilder->andWhere('dt.hasFirmware1 = :hasFirmware OR dt.hasFirmware2 = :hasFirmware OR dt.hasFirmware3 = :hasFirmware');
        $queryBuilder->setParameter('hasFirmware', true);
        $queryBuilder->andWhere('dt.enabled = :enabled');
        $queryBuilder->setParameter('enabled', true);

        return $queryBuilder->getQuery()->getResult();
    }

    #[Rest\Get('/vpn/device/types')]
    #[Api\Summary('Get device types that supports VPN')]
    #[Api\Response200ArraySubjectGroups(class: DeviceType::class, description: 'Array of device types that supports VPN')]
    #[Security("is_granted('ROLE_ADMIN_VPN') or is_granted('ROLE_VPN')")]
    #[Areas(['admin:vpnsecuritysuite', 'vpnsecuritysuite'])]
    public function vpnDeviceTypesAction()
    {
        // No need to check license because both security roles above would not be assigned
        $queryBuilder = $this->getRepository(DeviceType::class)->createQueryBuilder('dt');
        $queryBuilder->andWhere('dt.hasVpn = :hasVpn');
        $queryBuilder->setParameter('hasVpn', true);
        $queryBuilder->andWhere('dt.enabled = :enabled');
        $queryBuilder->setParameter('enabled', true);

        return $queryBuilder->getQuery()->getResult();
    }

    #[Rest\Get('/vpn/devices')]
    #[Api\Summary('Get devices that supports VPN')]
    #[Api\Response200ArraySubjectGroups(class: Device::class, description: 'Array of devices that supports VPN')]
    #[Security("is_granted('ROLE_ADMIN_VPN') or is_granted('ROLE_VPN')")]
    #[Areas(['admin:vpnsecuritysuite', 'vpnsecuritysuite'])]
    public function vpnDevicesAction()
    {
        // No need to check license because both security roles above would not be assigned
        $queryBuilder = $this->getRepository(Device::class)->createQueryBuilder('d');
        $queryBuilder->leftJoin('d.deviceType', 'dt');
        $queryBuilder->andWhere('dt.hasVpn = :hasVpn');
        $queryBuilder->setParameter('hasVpn', true);

        $accessTagsQueryApendix = '';

        if (!$this->isAllDevicesGranted() && $this->isGranted('ROLE_VPN')) {
            $queryBuilder->leftJoin('d.endpointDevices', 'ed');
            $accessTagsQueryApendix = 'OR :accessTags MEMBER OF ed.accessTags';
        }

        $this->applyUserAccessTagsQueryModification($queryBuilder, 'd', $accessTagsQueryApendix);

        return $queryBuilder->getQuery()->getResult();
    }

    #[Rest\Get('/vpn/deviceendpointdevices')]
    #[Api\Summary('Get endpoint devices that supports VPN')]
    #[Api\Response200ArraySubjectGroups(class: DeviceEndpointDevice::class, description: 'Array of endpoint devices that supports VPN')]
    #[Security("is_granted('ROLE_ADMIN_VPN') or is_granted('ROLE_VPN')")]
    #[Areas(['admin:vpnsecuritysuite', 'vpnsecuritysuite'])]
    public function vpnDeviceEndpointDevicesAction()
    {
        // No need to check license because both security roles above would not be assigned
        $queryBuilder = $this->getRepository(DeviceEndpointDevice::class)->createQueryBuilder('ded');
        $queryBuilder->leftJoin('ded.device', 'd');
        $queryBuilder->leftJoin('d.deviceType', 'dt');
        $queryBuilder->andWhere('dt.hasVpn = :hasVpn');
        $queryBuilder->setParameter('hasVpn', true);

        $this->applyUserAccessTagsQueryModification($queryBuilder, 'ded');

        return $queryBuilder->getQuery()->getResult();
    }

    #[Rest\Get('/devicetonetwork/device/types')]
    #[Api\Summary('Get device types that supports device to network connection')]
    #[Api\Response200ArraySubjectGroups(class: DeviceType::class, description: 'Array of device types that supports device to network connection')]
    #[Security("is_granted('ROLE_ADMIN_VPN')")]
    #[Areas(['admin:vpnsecuritysuite'])]
    public function deviceToNetworkDeviceTypesAction()
    {
        // No need to check license because security role above would not be assigned
        $queryBuilder = $this->getRepository(DeviceType::class)->createQueryBuilder('dt');
        $queryBuilder->andWhere('dt.hasDeviceToNetworkConnection = :hasDeviceToNetworkConnection');
        $queryBuilder->setParameter('hasDeviceToNetworkConnection', true);
        $queryBuilder->andWhere('dt.enabled = :enabled');
        $queryBuilder->setParameter('enabled', true);

        return $queryBuilder->getQuery()->getResult();
    }

    #[Rest\Get('/devicetonetwork/devices')]
    #[Api\Summary('Get devices that supports device to network connection')]
    #[Api\Response200ArraySubjectGroups(class: Device::class, description: 'Array of devices that supports device to network connection')]
    #[Security("is_granted('ROLE_ADMIN_VPN')")]
    #[Areas(['admin:vpnsecuritysuite'])]
    public function deviceToNetworkDevicesAction()
    {
        // No need to check license because security role above would not be assigned
        $queryBuilder = $this->getRepository(Device::class)->createQueryBuilder('d');
        $queryBuilder->leftJoin('d.deviceType', 'dt');
        $queryBuilder->andWhere('dt.hasDeviceToNetworkConnection = :hasDeviceToNetworkConnection');
        $queryBuilder->setParameter('hasDeviceToNetworkConnection', true);

        $this->applyUserAccessTagsQueryModification($queryBuilder, 'd');

        return $queryBuilder->getQuery()->getResult();
    }

    #[Rest\Get('/maintenance/schedules')]
    #[Api\Summary('Get maintenance schedules')]
    #[Api\Response200ArraySubjectGroups(class: MaintenanceSchedule::class)]
    #[Security("is_granted('ROLE_ADMIN')")]
    #[Areas(['admin'])]
    public function maintenanceSchedulesAction()
    {
        return $this->getRepository(MaintenanceSchedule::class)->findAll();
    }

    protected function removeDisabledDeviceTypes(Collection|array $deviceTypes): array
    {
        $returnDeviceTypes = [];
        foreach ($deviceTypes as $deviceType) {
            if ($deviceType->getIsAvailable()) {
                $returnDeviceTypes[] = $deviceType;
            }
        }

        return $returnDeviceTypes;
    }
}
