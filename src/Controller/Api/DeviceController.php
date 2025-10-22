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
use App\Deny\DeviceDeny;
use App\Deny\TemplateDenyHelperTrait;
use App\Entity\CertificateType;
use App\Entity\Config;
use App\Entity\Device;
use App\Entity\DeviceEndpointDevice;
use App\Entity\DeviceMasquerade;
use App\Entity\DeviceTypeSecret;
use App\Entity\DeviceVariable;
use App\Enum\CertificateEntity;
use App\Enum\Feature;
use App\Exception\LogsException;
use App\Form\BatchAccessTagsType;
use App\Form\BatchFlagType;
use App\Form\BatchLabelsType;
use App\Form\BatchVariableAddType;
use App\Form\BatchVariableDeleteType;
use App\Form\CertificateUploadFilesType;
use App\Form\CertificateUploadPkcs12Type;
use App\Form\DeviceCreateType;
use App\Form\DeviceDisableType;
use App\Form\DeviceEditType;
use App\Form\DeviceEnableType;
use App\Form\DeviceTemplateApplyType;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\ConfigManagerTrait;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Helper\EventDispatcherTrait;
use App\Service\Helper\LockManagerTrait;
use App\Service\Helper\ValidatorTrait;
use App\Service\Helper\VpnManagerTrait;
use App\Service\Trait\CertificateTypeHelperTrait;
use App\Trait\ApiCertificatesAllActionsTrait;
use App\Trait\ApiVpnCloseConnectionTrait;
use App\Trait\ApiVpnDownloadConfigurationTrait;
use App\Trait\ApiVpnOpenConnectionTrait;
use Carve\ApiBundle\Attribute\AddRoleBasedSerializerGroups;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Deny\AbstractApiObjectDeny;
use Carve\ApiBundle\Enum\BatchResultStatus;
use Carve\ApiBundle\Exception\RequestExecutionException;
use Carve\ApiBundle\Model\BatchResult;
use Carve\ApiBundle\Model\ListQueryFilterInterface;
use Carve\ApiBundle\Model\ListQuerySortingInterface;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiEditTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Rest\Route('/device')]
#[Api\Resource(
    class: Device::class,
    createFormClass: DeviceCreateType::class,
    editFormClass: DeviceEditType::class,
    denyClass: DeviceDeny::class,
    listFormFilterByAppend: ['config1', 'config2', 'config3', 'firmware1', 'firmware2', 'firmware3', 'isCertificateExpired', 'hasCertificate'],
    listFormSortingFieldAppend: ['certificateSubject', 'certificateCaSubject', 'hasCertificate', 'isCertificateExpired', 'certificateValidTo']
)]
#[Rest\View(serializerGroups: ['identification', 'device:public', 'gsm:public', 'communication:public', 'firmwareStatus', 'timestampable', 'blameable', 'deny'])]
#[AddRoleBasedSerializerGroups('ROLE_ADMIN', ['device:admin', 'gsm:admin', 'communication:admin', 'certificate:admin'])]
#[AddRoleBasedSerializerGroups('ROLE_ADMIN_VPN', ['device:adminVpn', 'device:vpnDevicePublic', 'device:openVpnPublic', 'device:vpnClientPublic', 'vpnConnection:device'])]
#[AddRoleBasedSerializerGroups('ROLE_SMARTEMS', ['device:smartems', 'gsm:smartems', 'communication:smartems', 'certificate:smartems'])]
#[AddRoleBasedSerializerGroups(
    'ROLE_VPN',
    ['device:vpn', 'certificate:vpn', 'device:vpnDevicePublic', 'device:openVpnPublic', 'device:vpnClientPublic', 'vpnConnection:device']
)]
#[AddRoleBasedSerializerGroups('ROLE_VPN_ENDPOINTDEVICES', ['device:vpnEndpointDevices'])]
#[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS') or is_granted('ROLE_VPN')")]
#[Areas(['admin', 'smartems', 'vpnsecuritysuite'])]
class DeviceController extends AbstractApiController
{
    use ValidatorTrait;
    use ApiCertificatesAllActionsTrait;
    use ApiVpnDownloadConfigurationTrait;
    use ApiVpnCloseConnectionTrait;
    use ApiVpnOpenConnectionTrait;
    use ApiCreateTrait;
    use ApiDeleteTrait;
    use ApiEditTrait;
    use ApiListTrait;
    use VpnManagerTrait;
    use DeviceCommunicationFactoryTrait;
    use ConfigManagerTrait;
    use SecurityHelperTrait;
    use TemplateDenyHelperTrait;
    use CertificateTypeHelperTrait;
    use EventDispatcherTrait;
    use LockManagerTrait;

    // DeviceSecretController is based on Device access, if any access to Devices is changes, please also update DeviceSecretController
    /**
     * Method checks your credentials and removes:
     * - Not owned accessTags and endpointDevices
     * - Not owned VPN connections from device and connected endpoint devices.
     * - Fill owned VPN connections from device and connected endpoint devices.
     */
    protected function modifyResponseObject(object $object): void
    {
        // Reinstall config flags are only serialized in this controller, so they need to be updated not to confuse user
        if ($object->getDeviceType()->getHasAlwaysReinstallConfig1()) {
            $object->setReinstallConfig1(true);
        }

        if ($object->getDeviceType()->getHasAlwaysReinstallConfig2()) {
            $object->setReinstallConfig2(true);
        }

        if ($object->getDeviceType()->getHasAlwaysReinstallConfig3()) {
            $object->setReinstallConfig3(true);
        }

        $this->removeNotOwnedAccessTags($object);

        if (!$this->isAllDevicesGranted()) {
            $endpointDevices = new ArrayCollection();
            foreach ($object->getEndpointDevices() as $endpointDevice) {
                foreach ($endpointDevice->getAccessTags() as $accessTag) {
                    if ($this->getUser()->getAccessTags()->contains($accessTag)) {
                        $endpointDevices->add($endpointDevice);
                        break;
                    }
                }

                $this->removeNotOwnedAccessTags($endpointDevice);
            }

            $object->setEndpointDevices($endpointDevices);
        }

        $this->removeNotOwnedVpnConnections($object);

        $this->fillOwnedVpnConnections($object);
    }

    protected function modifyQueryBuilder(QueryBuilder $queryBuilder, string $alias): void
    {
        $accessTagsQueryApendix = '';
        if (!$this->isAllDevicesGranted() && $this->isGranted('ROLE_VPN')) {
            $queryBuilder->leftJoin($alias.'.endpointDevices', 'ed');
            $accessTagsQueryApendix = 'OR :accessTags MEMBER OF ed.accessTags';
        }

        $this->applyUserAccessTagsQueryModification($queryBuilder, $alias, $accessTagsQueryApendix);
    }

    protected function modifyFilter(ListQueryFilterInterface $filter, QueryBuilder $queryBuilder, string $alias): bool
    {
        // Column security handled by #AddRoleBasedSerializerGroups
        // Same logic is used in processEdit in ConfigController to set reinstall config flag to connected devices
        $configFirmwareFilters = ['config1', 'config2', 'config3', 'firmware1', 'firmware2', 'firmware3'];
        $filterBy = $filter->getFilterBy();
        // Using array because modifyFilter is executed for each filter separately
        // Second condition checks if fiter join query is already added (by previous filter) - only zero or one join should be added for any amount of active filters
        if (in_array($filterBy, $configFirmwareFilters) && !in_array('t', $queryBuilder->getAllAliases())) {
            $queryBuilder->leftJoin($alias.'.template', 't');
            $queryBuilder->leftJoin('t.productionTemplate', 'tvp');
            $queryBuilder->leftJoin('t.stagingTemplate', 'tvs');
            $queryBuilder->andWhere('('.$alias.'.staging = true AND tvs.'.$filterBy.' = :'.$filterBy.') OR ('.$alias.'.staging = false AND tvp.'.$filterBy.' = :'.$filterBy.')');
            $queryBuilder->setParameter($filterBy, $filter->getFilterValue());

            return true;
        }

        // Using array because modifyFilter is executed for each filter separately
        // second condition checks if fiter join query is already added (by previous filter) - only zero or one join should be added for any amount of active filters
        $vpnCertificateFields = ['isCertificateExpired', 'hasCertificate'];
        if (in_array($filter->getFilterBy(), $vpnCertificateFields) && !in_array('pc', $queryBuilder->getAllAliases())) {
            $queryBuilder->leftJoin($alias.'.certificates', 'pc');
            // Condition IS NULL is required so query will not filter out devices without certificates in deviceVpn certificate type
            $queryBuilder->andWhere('pc.certificateType = :certificateType OR pc.certificateType IS NULL');
            $queryBuilder->setParameter('certificateType', $this->getDeviceVpnCertificateType());
        }

        // At this point certificate entity is joined to query with alias 'pc' (for deviceVpn certificate only)
        // Code below applies specific conditions for each field
        if ('isCertificateExpired' === $filter->getFilterBy()) {
            $queryBuilder->andWhere('pc.certificateValidTo IS NOT NULL');

            if ($filter->getFilterValue()) {
                $queryBuilder->andWhere('pc.certificateValidTo <= CURRENT_TIMESTAMP()');
            } else {
                $queryBuilder->andWhere('pc.certificateValidTo > CURRENT_TIMESTAMP()');
            }

            return true;
        }

        if ('hasCertificate' === $filter->getFilterBy()) {
            if ($filter->getFilterValue()) {
                $queryBuilder->andWhere('pc.certificate IS NOT NULL');
                $queryBuilder->andWhere('pc.certificateCa IS NOT NULL');
                $queryBuilder->andWhere('pc.privateKey IS NOT NULL');
            } else {
                $queryBuilder->andWhere('(pc.certificate IS NULL OR pc.certificateCa IS NULL OR pc.privateKey IS NULL )');
            }

            return true;
        }

        return false;
    }

    protected function modifySorting(ListQuerySortingInterface $sorting, QueryBuilder $queryBuilder, string $alias): bool
    {
        // Column security handled by #AddRoleBasedSerializerGroups
        if ('virtualSubnet' === $sorting->getField()) {
            $queryBuilder->addOrderBy($alias.'.virtualSubnetIpSortable', $sorting->getDirection()->value);

            return true;
        }

        if ('virtualIp' === $sorting->getField()) {
            $queryBuilder->addOrderBy($alias.'.virtualIpSortable', $sorting->getDirection()->value);

            return true;
        }

        if ('vpnIp' === $sorting->getField()) {
            $queryBuilder->addOrderBy($alias.'.vpnIpSortable', $sorting->getDirection()->value);

            return true;
        }

        // Using array because modifySorting is executed for each sorting separately
        // second condition checks if sorting join query is already added (by previous sorting) - only zero or one join should be added for any amount of active sortings
        $vpnCertificateFields = ['certificateSubject', 'certificateCaSubject', 'hasCertificate', 'isCertificateExpired', 'certificateValidTo'];
        if (in_array($sorting->getField(), $vpnCertificateFields) && !in_array('pc', $queryBuilder->getAllAliases())) {
            $queryBuilder->leftJoin($alias.'.certificates', 'pc');
            // Condition IS NULL is required so query will not filter out devices without certificates in deviceVpn certificate type
            $queryBuilder->andWhere('pc.certificateType = :certificateType OR pc.certificateType IS NULL');
            $queryBuilder->setParameter('certificateType', $this->getDeviceVpnCertificateType());
        }

        // At this point certificate entity is joined to query with alias 'pc' (for deviceVpn certificate only)
        // Code below applies specific conditions for each field
        if ('certificateSubject' === $sorting->getField()) {
            $queryBuilder->addOrderBy('pc.certificateSubject', $sorting->getDirection()->value);

            return true;
        }

        if ('certificateCaSubject' === $sorting->getField()) {
            $queryBuilder->addOrderBy('pc.certificateCaSubject', $sorting->getDirection()->value);

            return true;
        }

        if ('certificateValidTo' === $sorting->getField()) {
            $queryBuilder->addOrderBy('pc.certificateValidTo', $sorting->getDirection()->value);

            return true;
        }

        if ('hasCertificate' === $sorting->getField()) {
            $queryBuilder->addOrderBy('pc.certificate', $sorting->getDirection()->value);
            $queryBuilder->addOrderBy('pc.certificateCa', $sorting->getDirection()->value);
            $queryBuilder->addOrderBy('pc.privateKey', $sorting->getDirection()->value);

            return true;
        }

        if ('isCertificateExpired' === $sorting->getField()) {
            $queryBuilder->addOrderBy('CASE WHEN pc.certificateValidTo IS NULL THEN 0 ELSE 1 END', 'DESC');
            $queryBuilder->addOrderBy('pc.certificateValidTo', $sorting->getDirection()->value);

            return true;
        }

        return false;
    }

    #[Rest\Get('/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Get {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to return')]
    #[Api\Response200SubjectGroups]
    #[Api\Response404Id]
    public function getAction(int $id)
    {
        $object = $this->find($id, AbstractApiObjectDeny::GET);

        $this->modifyResponseObject($object);

        $this->fillHasDeviceSecrets($object);

        return $object;
    }

    #[Rest\Post('/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Edit {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to edit')]
    #[Api\RequestBodyEdit]
    #[Api\Response200SubjectGroups('Returns edited {{ subjectLower }}')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function editAction(Request $request, int $id)
    {
        $object = $this->find($id, AbstractApiObjectDeny::EDIT);

        $object->setLock($this->lockManager->getDeviceLock($object));
        foreach ($object->getEndpointDevices() as $endpointDevice) {
            $endpointDevice->setLock($this->lockManager->getEndpointDeviceLock($endpointDevice));
        }

        return $this->handleForm($this->getEditFormClass(), $request, [$this, 'processEdit'], $object, $this->getEditFormOptions());
    }

    #[Rest\Post('/batch/variable/add')]
    #[Api\Summary('Add variable to multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchVariableAddType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function batchVariableAddAction(Request $request)
    {
        $process = function (Device $device, FormInterface $form) {
            $name = $form->get('name')->getData();
            $variableValue = $form->get('variableValue')->getData();

            $queryBuilder = $this->getRepository(DeviceVariable::class)->createQueryBuilder('dv');
            $queryBuilder->andWhere('dv.device = :device');
            // BINARY is used to achieve case sensitive search
            $queryBuilder->andWhere('dv.name = BINARY(:name)');
            $queryBuilder->setParameter('device', $device);
            $queryBuilder->setParameter('name', $name);

            $deviceVariable = $queryBuilder->getQuery()->getOneOrNullResult();
            $found = $deviceVariable ? true : false;

            if (!$found) {
                $deviceVariable = new DeviceVariable();
                $deviceVariable->setName($name);
                $device->addVariable($deviceVariable);
            }

            $deviceVariable->setVariableValue($variableValue);

            $errors = $this->validator->validate($deviceVariable, null, ['Default', 'device:common']);
            if (0 === count($errors)) {
                $this->entityManager->persist($deviceVariable);
            } else {
                if (!$found) {
                    // Only removeVariable when $deviceVariable has just been created
                    $device->removeVariable($deviceVariable);
                }

                // Pick first error as representative
                $errorRepresentative = $errors[0];

                return new BatchResult($device, BatchResultStatus::ERROR, $errorRepresentative->getMessage(), $errorRepresentative->getParameters());
            }
        };

        return $this->handleBatchForm($process, $request, DeviceDeny::VARIABLE_ADD, null, BatchVariableAddType::class);
    }

    #[Rest\Post('/batch/variable/delete')]
    #[Api\Summary('Delete variable from multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchVariableDeleteType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function batchVariableDeleteAction(Request $request)
    {
        $process = function (Device $device, FormInterface $form) {
            $name = $form->get('name')->getData();

            $queryBuilder = $this->getRepository(DeviceVariable::class)->createQueryBuilder('dv');
            $queryBuilder->andWhere('dv.device = :device');
            // BINARY is used to achieve case sensitive search
            $queryBuilder->andWhere('dv.name = BINARY(:name)');
            $queryBuilder->setParameter('device', $device);
            $queryBuilder->setParameter('name', $name);

            $deviceVariable = $queryBuilder->getQuery()->getOneOrNullResult();
            if (!$deviceVariable) {
                return new BatchResult($device, BatchResultStatus::SKIPPED, 'batch.device.variableDelete.skipped.missing');
            }

            $this->entityManager->remove($deviceVariable);
        };

        return $this->handleBatchForm($process, $request, DeviceDeny::VARIABLE_DELETE, null, BatchVariableDeleteType::class);
    }

    #[Rest\Post('/batch/reinstallconfig1')]
    #[Api\Summary('Update reinstall primary config in multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchFlagType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function batchReinstallConfig1Action(Request $request)
    {
        $process = function (Device $device, FormInterface $form) {
            $flag = $form->get('flag')->getData();

            $deviceType = $device->getDeviceType();
            if (!$deviceType->getHasConfig1()) {
                return new BatchResult($device, BatchResultStatus::ERROR, 'validation.device.config1Disabled');
            }
            if ($deviceType->getHasAlwaysReinstallConfig1()) {
                return new BatchResult($device, BatchResultStatus::SKIPPED, 'validation.device.config1Always');
            }

            $device->setReinstallConfig1($flag);

            $this->entityManager->persist($device);
        };

        return $this->handleBatchForm($process, $request, DeviceDeny::BATCH_REINSTALL_CONFIG, null, BatchFlagType::class);
    }

    #[Rest\Post('/batch/reinstallconfig2')]
    #[Api\Summary('Update reinstall secondary config in multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchFlagType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function batchReinstallConfig2Action(Request $request)
    {
        $process = function (Device $device, FormInterface $form) {
            $flag = $form->get('flag')->getData();

            $deviceType = $device->getDeviceType();
            if (!$deviceType->getHasConfig2()) {
                return new BatchResult($device, BatchResultStatus::ERROR, 'validation.device.config2Disabled');
            }
            if ($deviceType->getHasAlwaysReinstallConfig2()) {
                return new BatchResult($device, BatchResultStatus::SKIPPED, 'validation.device.config2Always');
            }

            $device->setReinstallConfig2($flag);

            $this->entityManager->persist($device);
        };

        return $this->handleBatchForm($process, $request, DeviceDeny::BATCH_REINSTALL_CONFIG, null, BatchFlagType::class);
    }

    #[Rest\Post('/batch/reinstallconfig3')]
    #[Api\Summary('Update reinstall tertiary config in multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchFlagType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function batchReinstallConfig3Action(Request $request)
    {
        $process = function (Device $device, FormInterface $form) {
            $flag = $form->get('flag')->getData();

            $deviceType = $device->getDeviceType();
            if (!$deviceType->getHasConfig3()) {
                return new BatchResult($device, BatchResultStatus::ERROR, 'validation.device.config3Disabled');
            }
            if ($deviceType->getHasAlwaysReinstallConfig3()) {
                return new BatchResult($device, BatchResultStatus::SKIPPED, 'validation.device.config3Always');
            }

            $device->setReinstallConfig3($flag);

            $this->entityManager->persist($device);
        };

        return $this->handleBatchForm($process, $request, DeviceDeny::BATCH_REINSTALL_CONFIG, null, BatchFlagType::class);
    }

    #[Rest\Post('/batch/reinstallfirmware1')]
    #[Api\Summary('Update reinstall primary firmware in multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchFlagType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function batchReinstallFirmware1Action(Request $request)
    {
        $process = function (Device $device, FormInterface $form) {
            $flag = $form->get('flag')->getData();

            $deviceType = $device->getDeviceType();
            if (!$deviceType->getHasFirmware1()) {
                return new BatchResult($device, BatchResultStatus::ERROR, 'validation.device.firmware1Disabled');
            }

            $device->setReinstallFirmware1($flag);

            $this->entityManager->persist($device);
        };

        return $this->handleBatchForm($process, $request, DeviceDeny::BATCH_REINSTALL_FIRMWARE, null, BatchFlagType::class);
    }

    #[Rest\Post('/batch/reinstallfirmware2')]
    #[Api\Summary('Update reinstall secondary firmware in multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchFlagType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function batchReinstallFirmware2Action(Request $request)
    {
        $process = function (Device $device, FormInterface $form) {
            $flag = $form->get('flag')->getData();

            $deviceType = $device->getDeviceType();
            if (!$deviceType->getHasFirmware2()) {
                return new BatchResult($device, BatchResultStatus::ERROR, 'validation.device.firmware2Disabled');
            }

            $device->setReinstallFirmware2($flag);

            $this->entityManager->persist($device);
        };

        return $this->handleBatchForm($process, $request, DeviceDeny::BATCH_REINSTALL_FIRMWARE, null, BatchFlagType::class);
    }

    #[Rest\Post('/batch/reinstallfirmware3')]
    #[Api\Summary('Update reinstall tertiary firmware in multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchFlagType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function batchReinstallFirmware3Action(Request $request)
    {
        $process = function (Device $device, FormInterface $form) {
            $flag = $form->get('flag')->getData();

            $deviceType = $device->getDeviceType();
            if (!$deviceType->getHasFirmware3()) {
                return new BatchResult($device, BatchResultStatus::ERROR, 'validation.device.firmware3Disabled');
            }

            $device->setReinstallFirmware3($flag);

            $this->entityManager->persist($device);
        };

        return $this->handleBatchForm($process, $request, DeviceDeny::BATCH_REINSTALL_FIRMWARE, null, BatchFlagType::class);
    }

    #[Rest\Post('/batch/requestdiagnosedata')]
    #[Api\Summary('Update request diagnose data in multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchFlagType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function batchRequestDiagnoseDataAction(Request $request)
    {
        $process = function (Device $device, FormInterface $form) {
            $flag = $form->get('flag')->getData();

            $deviceType = $device->getDeviceType();
            if (!$deviceType->getHasRequestDiagnose()) {
                return new BatchResult($device, BatchResultStatus::ERROR, 'validation.device.requestDiagnoseDataDisabled');
            }

            $device->setRequestDiagnoseData($flag);

            $this->entityManager->persist($device);
        };

        return $this->handleBatchForm($process, $request, DeviceDeny::BATCH_REQUEST_DIAGNOSE, null, BatchFlagType::class);
    }

    #[Rest\Post('/batch/requestconfigdata')]
    #[Api\Summary('Update request config data in multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchFlagType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function batchRequestConfigDataAction(Request $request)
    {
        $process = function (Device $device, FormInterface $form) {
            $flag = $form->get('flag')->getData();

            $deviceType = $device->getDeviceType();
            if (!$deviceType->getHasRequestConfig()) {
                return new BatchResult($device, BatchResultStatus::ERROR, 'validation.device.requestConfigDataDisabled');
            }

            $device->setRequestConfigData($flag);

            $this->entityManager->persist($device);
        };

        return $this->handleBatchForm($process, $request, DeviceDeny::BATCH_REQUEST_CONFIG, null, BatchFlagType::class);
    }

    #[Rest\Post('/batch/accesstags/add')]
    #[Api\Summary('Add access tags to multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchAccessTagsType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function batchAccessTagsAddAction(Request $request)
    {
        $process = function (Device $device, FormInterface $form) {
            $accessTags = $form->get('accessTags')->getData();

            foreach ($accessTags as $accessTag) {
                if ($this->isAllDevicesGranted() || $this->getUser()->getAccessTags()->contains($accessTag)) {
                    $device->addAccessTag($accessTag);
                } else {
                    return new BatchResult($device, BatchResultStatus::ERROR, 'validation.device.invalidAccessTag');
                }
            }

            $this->entityManager->persist($device);
        };

        return $this->handleBatchForm($process, $request, DeviceDeny::ACCESS_TAG_ADD, null, BatchAccessTagsType::class);
    }

    #[Rest\Post('/batch/accesstags/delete')]
    #[Api\Summary('Remove access tags from multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchAccessTagsType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function batchAccessTagsDeleteAction(Request $request)
    {
        $process = function (Device $device, FormInterface $form) {
            $accessTags = $form->get('accessTags')->getData();

            $usersAccessTags = new ArrayCollection();
            if (!$this->isAllDevicesGranted()) {
                foreach ($device->getAccessTags() as $accessTag) {
                    if ($this->getUser()->getAccessTags()->contains($accessTag)) {
                        $usersAccessTags->add($accessTag);
                    }
                }
            }

            foreach ($accessTags as $accessTag) {
                if ($this->isAllDevicesGranted() || $this->getUser()->getAccessTags()->contains($accessTag)) {
                    if ($this->isAllDevicesGranted() || $usersAccessTags->count() > 1) {
                        $device->removeAccessTag($accessTag);
                        $usersAccessTags->removeElement($accessTag);
                    } else {
                        return new BatchResult($device, BatchResultStatus::ERROR, 'validation.device.cannotRemoveAllAccessTag');
                    }
                }
            }

            $this->entityManager->persist($device);
        };

        return $this->handleBatchForm($process, $request, DeviceDeny::ACCESS_TAG_DELETE, null, BatchAccessTagsType::class);
    }

    #[Rest\Post('/batch/labels/add')]
    #[Api\Summary('Add labels to multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchLabelsType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function batchLabelsAddAction(Request $request)
    {
        $process = function (Device $device, FormInterface $form) {
            $labels = $form->get('labels')->getData();

            foreach ($labels as $label) {
                $device->addLabel($label);
            }

            $this->entityManager->persist($device);
        };

        return $this->handleBatchForm($process, $request, null, null, BatchLabelsType::class);
    }

    #[Rest\Post('/batch/labels/delete')]
    #[Api\Summary('Remove labels from multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchLabelsType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function batchLabelsDeleteAction(Request $request)
    {
        $process = function (Device $device, FormInterface $form) {
            $labels = $form->get('labels')->getData();

            foreach ($labels as $label) {
                $device->removeLabel($label);
            }

            $this->entityManager->persist($device);
        };

        return $this->handleBatchForm($process, $request, null, null, BatchLabelsType::class);
    }

    #[Rest\Post('/{id}/disable')]
    #[Api\Summary('Disable {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to disable')]
    #[Api\RequestBody(content: new NA\Model(type: DeviceDisableType::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    #[Api\Response404Id]
    #[Areas(['admin', 'smartems'])]
    public function disableAction(Request $request, int $id)
    {
        $object = $this->find($id, DeviceDeny::DISABLE);

        // We need to pre-set this to allow proper checks using validators
        $object->setEnabled(false);

        return $this->handleForm(DeviceDisableType::class, $request, function ($object) {
            // persist object before dispatchDeviceUpdated which can result in RequestExecutionException
            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $this->dispatchDeviceUpdated($object);

            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $this->modifyResponseObject($object);

            return $object;
        }, $object);
    }

    #[Rest\Post('/{id}/enable', requirements: ['id' => '\d+'])]
    #[Api\Summary('Enable {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to enable')]
    #[Api\RequestBody(content: new NA\Model(type: DeviceEnableType::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response404Id]
    #[Areas(['admin', 'smartems'])]
    public function enableAction(Request $request, int $id)
    {
        $object = $this->find($id, DeviceDeny::ENABLE);

        // We need to pre-set this to allow proper checks using validators
        $object->setEnabled(true);

        return $this->handleForm(DeviceEnableType::class, $request, function ($object) {
            // persist object before dispatchDeviceUpdated which can result in RequestExecutionException
            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $this->dispatchDeviceUpdated($object);

            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $this->modifyResponseObject($object);

            return $object;
        }, $object);
    }

    #[Rest\Post('/{id}/template/apply', requirements: ['id' => '\d+'])]
    #[Api\Summary('Apply template to {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to apply template to')]
    #[Api\RequestBody(content: new NA\Model(type: DeviceTemplateApplyType::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function templateApplyAction(Request $request, int $id)
    {
        $device = $this->find($id, DeviceDeny::TEMPLATE_APPLY);

        return $this->handleForm(DeviceTemplateApplyType::class, $request, function ($templateApply) use ($device) {
            $template = $templateApply->getTemplate();
            if (!$template) {
                $device->setTemplate(null);

                $this->entityManager->persist($device);
                $this->entityManager->flush();

                return $device;
            }

            if (!$this->isAllDevicesGranted()) {
                if ($this->getUser() != $template->getCreatedBy()) {
                    if (!$this->isAnyDeviceAccessibleUsingTemplate($template)) {
                        throw new RequestExecutionException('batch.device.templateApply.error.templateNoAccess');
                    }
                }
            }

            if ($device->getDeviceType() !== $template->getDeviceType()) {
                throw new RequestExecutionException('batch.device.templateApply.error.deviceTypeMismatch');
            }

            $templateVersion = $template->getStagingTemplate();
            $staging = $device->getStaging();
            if (!$templateVersion || !$staging) {
                $templateVersion = $template->getProductionTemplate();
            }

            if (!$templateVersion) {
                throw new RequestExecutionException('batch.device.templateApply.error.'.($staging ? 'templateVersionMissing' : 'templateVersionProductionMissing'));
            }

            $applyEndpointDevices = $this->isGranted('ROLE_ADMIN_VPN') && $templateApply->getApplyEndpointDevices() ? true : false;
            $hasVirtualSubnetCidr = $templateVersion->getVirtualSubnetCidr() ? true : false;

            // Verify ASAP whether there is any connection to a device or any endpoint device.
            // Simplify lock logic by denying any changes to virtualSubnetCidr and endpointDevices if there are any connections.
            // Ideal way would be to modify $device and validate it against "device:lock" and "deviceEndpointDevice:lock" groups.
            // Unfortunately this is very complex with current code. Modify after refactoring.
            if ($applyEndpointDevices && $hasVirtualSubnetCidr && $this->lockManager->shouldLockTemplateApplyEndpointDevices($device)) {
                throw new RequestExecutionException('batch.device.templateApply.error.templateApplyEndpointDevicesLocked');
            }

            $device->setTemplate($template);

            $executionException = new RequestExecutionException();

            if ($templateApply->getApplyLabels()) {
                $device->setLabels($templateVersion->getDeviceLabels());
            }

            if ($templateApply->getApplyVariables()) {
                // Create or edit variables that are included in a template
                foreach ($templateVersion->getVariables() as $key => $templateVariable) {
                    $variable = $device->getVariables()->get($key);

                    if (!$variable) {
                        $variable = new DeviceVariable();
                        $device->addVariable($variable);
                    }

                    $variable->setName($templateVariable->getName());
                    $variable->setVariableValue($templateVariable->getVariableValue());

                    $this->entityManager->persist($variable);
                }

                // Remove variables that are not included in a template
                $templateVariableKeys = $templateVersion->getVariables()->getKeys();
                $variableKeys = $device->getVariables()->getKeys();
                // Start from last key to avoid issues due to mutating $variables collection in device
                foreach (array_reverse($variableKeys) as $variableKey) {
                    if (in_array($variableKey, $templateVariableKeys)) {
                        continue;
                    }

                    $variable = $device->getVariables()->get($variableKey);
                    $device->removeVariable($variable);
                    $this->entityManager->remove($variable);
                }
            }

            if ($templateApply->getApplyAccessTags()) {
                $hasAnyAccessTag = false;
                if (!$this->isAllDevicesGranted()) {
                    foreach ($templateVersion->getAccessTags() as $accessTag) {
                        if ($this->getUser()->getAccessTags()->contains($accessTag)) {
                            $hasAnyAccessTag = true;
                            break;
                        }
                    }
                } else {
                    $hasAnyAccessTag = true;
                }

                if ($hasAnyAccessTag) {
                    $device->setAccessTags(new ArrayCollection());

                    $allAccessTagsApplied = true;

                    foreach ($templateVersion->getAccessTags() as $accessTag) {
                        if ($this->isAllDevicesGranted() || $this->getUser()->getAccessTags()->contains($accessTag)) {
                            $device->addAccessTag($accessTag);
                        } else {
                            $allAccessTagsApplied = false;
                        }
                    }

                    if (!$allAccessTagsApplied) {
                        $executionException->addWarning('batch.device.templateApply.warning.notAllAccessTagsWereApplied');
                    }
                } else {
                    $executionException->addWarning('batch.device.templateApply.warning.noAccessTagsWereAppliedYouWouldLoseAccess');
                }
            }

            if ($this->isGranted('ROLE_ADMIN_VPN') && $templateApply->getApplyMasquerade()) {
                if ($templateVersion->getMasqueradeType()) {
                    $device->setMasqueradeType($templateVersion->getMasqueradeType());

                    // Create or edit masquerades that are included in a template
                    foreach ($templateVersion->getMasquerades() as $key => $templateMasquerade) {
                        $masquerade = $device->getMasquerades()->get($key);

                        if (!$masquerade) {
                            $masquerade = new DeviceMasquerade();
                            $device->addMasquerade($masquerade);
                        }

                        $masquerade->setSubnet($templateMasquerade->getSubnet());

                        $this->entityManager->persist($masquerade);
                    }

                    // Remove masquerades that are not included in a template
                    $templateMasqueradeKeys = $templateVersion->getMasquerades()->getKeys();
                    $masqueradeKeys = $device->getMasquerades()->getKeys();
                    // Start from last key to avoid issues due to mutating $masquerades collection in device
                    foreach (array_reverse($masqueradeKeys) as $masqueradeKey) {
                        if (in_array($masqueradeKey, $templateMasqueradeKeys)) {
                            continue;
                        }

                        $masquerade = $device->getMasquerades()->get($masqueradeKey);
                        $device->removeMasquerade($masquerade);
                        $this->entityManager->remove($masquerade);
                    }
                } else {
                    $executionException->addWarning('batch.device.templateApply.warning.noMasqueradeTypeToApply');
                }
            }

            if ($this->isGranted('ROLE_ADMIN_VPN') && $templateApply->getApplyDeviceDescription()) {
                if ($templateVersion->getDeviceDescription()) {
                    $device->setDescription($templateVersion->getDeviceDescription());
                } else {
                    $executionException->addWarning('batch.device.templateApply.warning.noDeviceDescriptionToApply');
                }
            }

            if ($applyEndpointDevices) {
                if ($hasVirtualSubnetCidr) {
                    $device->setVirtualSubnetCidr($templateVersion->getVirtualSubnetCidr());

                    // Create or edit endpoint devices that are included in a template
                    foreach ($templateVersion->getEndpointDevices() as $key => $templateEndpointDevice) {
                        $endpointDevice = $device->getEndpointDevices()->get($key);

                        if (!$endpointDevice) {
                            $endpointDevice = new DeviceEndpointDevice();
                            $device->addEndpointDevice($endpointDevice);
                        }

                        $endpointDevice->setName($templateEndpointDevice->getName());
                        $endpointDevice->setDescription($templateEndpointDevice->getDescription());
                        $endpointDevice->setPhysicalIp($templateEndpointDevice->getPhysicalIp());
                        $endpointDevice->setPhysicalIpSortable(ip2long($templateEndpointDevice->getPhysicalIp()));
                        $endpointDevice->setVirtualIpHostPart($templateEndpointDevice->getVirtualIpHostPart());
                        $endpointDevice->setAccessTags($templateEndpointDevice->getAccessTags());

                        $this->entityManager->persist($endpointDevice);
                    }

                    // Remove endpoint devices that are not included in a template
                    $templateEndpointDeviceKeys = $templateVersion->getEndpointDevices()->getKeys();
                    $endpointDeviceKeys = $device->getEndpointDevices()->getKeys();
                    // Start from last key to avoid issues due to mutating $endpointDevices collection in device
                    foreach (array_reverse($endpointDeviceKeys) as $endpointDeviceKey) {
                        if (in_array($endpointDeviceKey, $templateEndpointDeviceKeys)) {
                            continue;
                        }

                        $endpointDevice = $device->getEndpointDevices()->get($endpointDeviceKey);

                        try {
                            $this->dispatchDeviceEndpointDevicePreRemove($endpointDevice);
                        } catch (LogsException $logsException) {
                            // TODO Needs a fix. This is a critical error and should stop the process of removing EDs (same happens when you delete ED via DEDController)
                            // TODO Probably this should be some kind of a "prework" to this whole template apply process.
                            $executionException->mergeAsWarnings($logsException);
                        }

                        $device->removeEndpointDevice($endpointDevice);
                        $this->entityManager->remove($endpointDevice);
                    }

                    // persist object before dispatchDeviceUpdated which can result in RequestExecutionException
                    $this->entityManager->persist($device);
                    $this->entityManager->flush();

                    try {
                        $this->dispatchDeviceUpdated($device);
                    } catch (LogsException $logsException) {
                        $executionException->mergeAsWarnings($logsException);
                    }
                } else {
                    $executionException->addWarning('batch.device.templateApply.warning.noVirtualSubnetCidrToApply');
                }
            }

            if ($templateApply->getReinstallConfig1() && !$device->getDeviceType()->getHasAlwaysReinstallConfig1()) {
                $device->setReinstallConfig1($templateApply->getReinstallConfig1());
            }

            if ($templateApply->getReinstallConfig2() && !$device->getDeviceType()->getHasAlwaysReinstallConfig2()) {
                $device->setReinstallConfig2($templateApply->getReinstallConfig2());
            }

            if ($templateApply->getReinstallConfig3() && !$device->getDeviceType()->getHasAlwaysReinstallConfig3()) {
                $device->setReinstallConfig3($templateApply->getReinstallConfig3());
            }

            if ($templateApply->getReinstallFirmware1()) {
                $device->setReinstallFirmware1($templateApply->getReinstallFirmware1());
            }

            if ($templateApply->getReinstallFirmware2()) {
                $device->setReinstallFirmware2($templateApply->getReinstallFirmware2());
            }

            if ($templateApply->getReinstallFirmware3()) {
                $device->setReinstallFirmware3($templateApply->getReinstallFirmware3());
            }

            $this->entityManager->persist($device);
            $this->entityManager->flush();

            $this->modifyResponseObject($device);

            if ($executionException->hasErrors()) {
                throw $executionException;
            }

            return $device;
        });
    }

    #[Rest\Get('/{id}/predefined/variables', requirements: ['id' => '\d+'])]
    #[Api\Summary('Get predefined variables for {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to return predefined variables')]
    #[Api\Response200(
        description: 'Returns predefined variables',
        content: new OA\JsonContent(example: '{"variable1": "Example value", "variable2": 1, "variable3": null, "variable4": {"1": "192.168.1.1"}}')
    )]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function getPredefinedVariablesAction(int $id)
    {
        $object = $this->find($id, DeviceDeny::PREDEFINED_VARIABLES);

        return $this->getDevicePredefinedVariables($object);
    }

    public function getDevicePredefinedVariables(Device $device): array
    {
        $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDevice($device);

        if (!$communicationProcedure) {
            // This should never happen
            return [];
        }

        return $communicationProcedure->getPredefinedDeviceVariables();
    }

    // Method provides array of available certificate types for all devices (used in batch enable/disable)
    #[Rest\Get('/certificate/types')]
    #[Api\Summary('Get list of available certificate types for {{ subjectPluralLower }}')]
    #[Api\Response200ArraySubjectGroups(CertificateType::class)]
    #[Security("is_granted('ROLE_ADMIN_SCEP')")]
    #[Areas(['admin:scep'])]
    public function getCertificateTypesAction()
    {
        return $this->getAvailableCertificateTypesForCertificateEntity(CertificateEntity::DEVICE);
    }

    #[Rest\Get('/{id}/generate/config/primary', requirements: ['id' => '\d+'])]
    #[Api\Summary('Get generated primary config for {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to return generated primary config')]
    #[Api\Response200(description: 'Generated primary config', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(type: 'string')))]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function generateConfigPrimaryAction(int $id)
    {
        $object = $this->find($id, DeviceDeny::GENERATE_CONFIG_PRIMARY);

        $configDevice = $this->configManager->generateDeviceConfig($object->getDeviceType(), $object, Feature::PRIMARY, false, false);
        if ($configDevice->isGenerated()) {
            return $configDevice->getConfigGenerated();
        }

        throw new RequestExecutionException($configDevice->getErrorMessageTemplate(), $configDevice->getErrorMessageTemplateVariables());
    }

    #[Rest\Get('/{id}/generate/config/secondary', requirements: ['id' => '\d+'])]
    #[Api\Summary('Get generated secondary config for {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to return generated secondary config')]
    #[Api\Response200(description: 'Generated secondary config', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(type: 'string')))]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function generateConfigSecondaryAction(int $id)
    {
        $object = $this->find($id, DeviceDeny::GENERATE_CONFIG_SECONDARY);

        $configDevice = $this->configManager->generateDeviceConfig($object->getDeviceType(), $object, Feature::SECONDARY, false, false);
        if ($configDevice->isGenerated()) {
            return $configDevice->getConfigGenerated();
        }

        throw new RequestExecutionException($configDevice->getErrorMessageTemplate(), $configDevice->getErrorMessageTemplateVariables());
    }

    #[Rest\Get('/{id}/generate/config/tertiary', requirements: ['id' => '\d+'])]
    #[Api\Summary('Get generated tertiary config for {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to return generated tertiary config')]
    #[Api\Response200(description: 'Generated tertiary config', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(type: 'string')))]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function generateConfigTertiaryAction(int $id)
    {
        $object = $this->find($id, DeviceDeny::GENERATE_CONFIG_TERTIARY);

        $configDevice = $this->configManager->generateDeviceConfig($object->getDeviceType(), $object, Feature::TERTIARY, false, false);
        if ($configDevice->isGenerated()) {
            return $configDevice->getConfigGenerated();
        }

        throw new RequestExecutionException($configDevice->getErrorMessageTemplate(), $configDevice->getErrorMessageTemplateVariables());
    }

    #[Rest\Get('/{id}/certificates')]
    #[Rest\View(serializerGroups: [
            'identification',
            'device:public',
            'certificate:admin',
            'certificate:vpn',
            'certificate:smartems',
            'certificate:private',
            'timestampable',
            'blameable',
            'deny',
        ]
    )]
    #[Api\Summary('Get device certificate information')]
    #[Api\Response200Groups(description: 'Returns device certificate information', content: new NA\Model(type: Device::class))]
    public function certificatesAction(int $id)
    {
        $object = $this->find($id, DeviceDeny::GET);

        foreach ($object->getUseableCertificates() as $useableCertificate) {
            $useableCertificate->getCertificate()->setDecryptedCertificateCa($this->certificateManager->getCertificateCa($useableCertificate->getCertificate()));
            $useableCertificate->getCertificate()->setDecryptedCertificate($this->certificateManager->getCertificate($useableCertificate->getCertificate()));
            $useableCertificate->getCertificate()->setDecryptedPrivateKey($this->certificateManager->getPrivateKey($useableCertificate->getCertificate()));
        }

        return $object;
    }

    protected function processEdit($object, FormInterface $form)
    {
        // Binding request data to form may result in a null value for $virtualSubnetCidr, and $masqueradeType we use default value from device type
        // We cannot use 'empty_data' option in form due to dependency on device type

        if (!$object->getVirtualSubnetCidr()) {
            $object->setVirtualSubnetCidr($object->getDeviceType()->getVirtualSubnetCidr());
        }

        if (!$object->getMasqueradeType()) {
            $object->setMasqueradeType($object->getDeviceType()->getMasqueradeType());
        }

        // Just to keep it clean
        // Decided not to use validator for easier UX during API call
        if ($object->getDeviceType()->getHasAlwaysReinstallConfig1()) {
            $object->setReinstallConfig1(false);
        }

        if ($object->getDeviceType()->getHasAlwaysReinstallConfig2()) {
            $object->setReinstallConfig2(false);
        }

        if ($object->getDeviceType()->getHasAlwaysReinstallConfig3()) {
            $object->setReinstallConfig3(false);
        }

        // persist object before dispatchDeviceUpdated which can result in RequestExecutionException
        $this->entityManager->persist($object);
        $this->entityManager->flush();

        $this->dispatchDeviceUpdated($object);
        $this->entityManager->persist($object);
        $this->entityManager->flush();

        $this->modifyResponseObject($object);

        return $object;
    }

    #[Rest\Post('/create')]
    #[Api\Summary('Create {{ subjectLower }}')]
    #[Api\RequestBodyCreate]
    #[Api\Response200SubjectGroups('Returns created {{ subjectLower }}')]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function createAction(Request $request)
    {
        return $this->handleForm($this->getCreateFormClass(), $request, [$this, 'processCreate'], $this->getCreateObject(), $this->getCreateFormOptions());
    }

    protected function processCreate($object, FormInterface $form)
    {
        // Binding request data to form may result in a null value for $virtualSubnetCidr, and $masqueradeType we use default value from device type
        // We cannot use 'empty_data' option in form due to dependency on device type
        if (!$object->getVirtualSubnetCidr()) {
            $object->setVirtualSubnetCidr($object->getDeviceType()->getVirtualSubnetCidr());
        }

        if (!$object->getMasqueradeType()) {
            $object->setMasqueradeType($object->getDeviceType()->getMasqueradeType());
        }

        // Just to keep it clean
        // Decided not to use validator for easier UX during API call
        if ($object->getDeviceType()->getHasAlwaysReinstallConfig1()) {
            $object->setReinstallConfig1(false);
        }

        if ($object->getDeviceType()->getHasAlwaysReinstallConfig2()) {
            $object->setReinstallConfig2(false);
        }

        if ($object->getDeviceType()->getHasAlwaysReinstallConfig3()) {
            $object->setReinstallConfig3(false);
        }

        $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($object->getDeviceType());
        if (!$communicationProcedure) {
            // This should never happen
            throw new \Exception('No communication procedure found');
        }
        $object->setIdentifier($communicationProcedure->generateIdentifier($object));
        $object->setUuid($communicationProcedure->getDeviceTypeUniqueUuid());
        $object->setHashIdentifier($communicationProcedure->getDeviceUniqueHashIdentifier());

        // persist object before dispatchDeviceUpdated which can result in RequestExecutionException
        $this->entityManager->persist($object);
        $this->entityManager->flush();

        // Filling deny to have correctly set $useableCertificates for automatic behaviours
        if ($this->hasDenyClass()) {
            $denyClass = $this->getDenyClass();
            $this->fillDeny($denyClass, $object);
        }

        $this->dispatchDeviceUpdated($object);

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        $this->modifyResponseObject($object);

        return $object;
    }

    protected function processDelete(object $object)
    {
        $this->dispatchDevicePreRemove($object);

        $this->entityManager->remove($object);
        $this->entityManager->flush();
    }

    protected function fillHasDeviceSecrets(Device $object): Device
    {
        // Make sure this query is consistent with query in DeviceSecretsController

        $alias = 'o';
        $queryBuilder = $this->getRepository(DeviceTypeSecret::class)->createQueryBuilder($alias);
        $queryBuilder->select('COUNT(DISTINCT o.id)');

        $queryBuilder->andWhere($alias.'.deviceType = :deviceType');
        $queryBuilder->setParameter('deviceType', $object->getDeviceType());

        if (!$this->isGranted('ROLE_ADMIN')) {
            $queryBuilder->andWhere(':accessTags MEMBER OF '.$alias.'.accessTags');
            $queryBuilder->setParameter('accessTags', $this->getUser()->getAccessTags());
        }

        $queryBuilder->setFirstResult(0);
        $queryBuilder->setMaxResults(1);
        $availableDeviceSecretsAmount = $queryBuilder->getQuery()->getSingleScalarResult();

        $object->setHasDeviceSecrets($availableDeviceSecretsAmount > 0);

        return $object;
    }

    // DeviceVPN certificate type actions wrapped into backwards compatible endpoint
    #[Rest\Get('/{id}/delete/certificate', requirements: ['id' => '\d+'])]
    #[Api\Summary('Delete {{ subjectLower }} uploaded device VPN certificate by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to delete uploaded device VPN certificate')]
    #[Api\Response204('Uploaded device VPN certificate successfully deleted')]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function deleteDeviceVpnCertificateAction(Request $request, int $id)
    {
        $deviceVpnCertificateType = $this->getDeviceVpnCertificateType();
        if (!$deviceVpnCertificateType) {
            throw new NotFoundHttpException();
        }

        return $this->deleteCertificateAction($request, $id, $deviceVpnCertificateType->getId());
    }

    #[Rest\Get('/{id}/download/ca', requirements: ['id' => '\d+'])]
    #[Api\Summary('Download CA device VPN certificate for {{ subjectLower }}')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to download CA of device VPN certificate')]
    #[Api\Response200(description: 'CA certificate', content: new OA\MediaType(mediaType: 'application/x-x509-ca-cert', schema: new OA\Schema(type: 'string')))]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function downloadDeviceVpnCaAction(Request $request, int $id)
    {
        $deviceVpnCertificateType = $this->getDeviceVpnCertificateType();
        if (!$deviceVpnCertificateType) {
            throw new NotFoundHttpException();
        }

        return $this->downloadCaAction($request, $id, $deviceVpnCertificateType->getId());
    }

    #[Rest\Get('/{id}/download/certificate', requirements: ['id' => '\d+'])]
    #[Api\Summary('Download device VPN certificate for {{ subjectLower }}')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to download device VPN certificate')]
    #[Api\Response200(description: 'Certificate', content: new OA\MediaType(mediaType: 'application/x-x509-user-cert', schema: new OA\Schema(type: 'string')))]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function downloadDeviceVpnCertificateAction(Request $request, int $id)
    {
        $deviceVpnCertificateType = $this->getDeviceVpnCertificateType();
        if (!$deviceVpnCertificateType) {
            throw new NotFoundHttpException();
        }

        return $this->downloadCertificateAction($request, $id, $deviceVpnCertificateType->getId());
    }

    #[Rest\Get('/{id}/download/pkcs12', requirements: ['id' => '\d+'])]
    #[Api\Summary('Download device VPN PKCS#12 for {{ subjectLower }}')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to download device VPN PKCS#12')]
    #[Api\Response200(description: 'PKCS#12', content: new OA\MediaType(mediaType: 'application/x-pkcs12', schema: new OA\Schema(type: 'string')))]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function downloadDeviceVpnPkcs12Action(Request $request, int $id)
    {
        $deviceVpnCertificateType = $this->getDeviceVpnCertificateType();
        if (!$deviceVpnCertificateType) {
            throw new NotFoundHttpException();
        }

        return $this->downloadPkcs12Action($request, $id, $deviceVpnCertificateType->getId());
    }

    #[Rest\Get('/{id}/download/private', requirements: ['id' => '\d+'])]
    #[Api\Summary('Download device VPN private key for {{ subjectLower }}')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to download device VPN private key')]
    #[Api\Response200(description: 'Private key', content: new OA\MediaType(mediaType: 'application/pkcs8', schema: new OA\Schema(type: 'string')))]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function downloadDeviceVpnPrivateAction(Request $request, int $id)
    {
        $deviceVpnCertificateType = $this->getDeviceVpnCertificateType();
        if (!$deviceVpnCertificateType) {
            throw new NotFoundHttpException();
        }

        return $this->downloadPrivateAction($request, $id, $deviceVpnCertificateType->getId());
    }

    #[Rest\Get('/{id}/generate/certificate', requirements: ['id' => '\d+'])]
    #[Api\Summary('Generate device VPN certificate for {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to generate device VPN certificate')]
    #[Api\Response200SubjectGroups]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN_SCEP')")]
    #[Areas(['admin:scep'])]
    public function generateDeviceVpnCertificateAction(Request $request, int $id)
    {
        $deviceVpnCertificateType = $this->getDeviceVpnCertificateType();
        if (!$deviceVpnCertificateType) {
            throw new NotFoundHttpException();
        }

        return $this->generateCertificateAction($request, $id, $deviceVpnCertificateType->getId());
    }

    #[Rest\Get('/{id}/revoke/certificate', requirements: ['id' => '\d+'])]
    #[Api\Summary('Revoke device VPN certificate for {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to revoke device VPN certificate')]
    #[Api\Response200SubjectGroups]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN_SCEP')")]
    #[Areas(['admin:scep'])]
    public function revokeDeviceVpnCertificateAction(Request $request, int $id)
    {
        $deviceVpnCertificateType = $this->getDeviceVpnCertificateType();
        if (!$deviceVpnCertificateType) {
            throw new NotFoundHttpException();
        }

        return $this->revokeCertificateAction($request, $id, $deviceVpnCertificateType->getId());
    }

    #[Rest\Post('/{id}/upload/files', requirements: ['id' => '\d+'])]
    #[Api\Summary('Upload device VPN CA certificate, certificate and private key for {{ subjectLower }}')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to upload device VPN CA certificate, certificate and private key')]
    #[Api\RequestBody(content: new NA\Model(type: CertificateUploadFilesType::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function uploadDeviceVpnFilesAction(Request $request, int $id)
    {
        $deviceVpnCertificateType = $this->getDeviceVpnCertificateType();
        if (!$deviceVpnCertificateType) {
            throw new NotFoundHttpException();
        }

        return $this->uploadFilesAction($request, $id, $deviceVpnCertificateType->getId());
    }

    #[Rest\Post('/{id}/upload/pkcs12', requirements: ['id' => '\d+'])]
    #[Api\Summary('Upload device VPN PKCS#12 for {{ subjectLower }}')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to upload device VPN PKCS#12')]
    #[Api\RequestBody(content: new NA\Model(type: CertificateUploadPkcs12Type::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function uploadDeviceVpnPkcs12Action(Request $request, int $id)
    {
        $deviceVpnCertificateType = $this->getDeviceVpnCertificateType();
        if (!$deviceVpnCertificateType) {
            throw new NotFoundHttpException();
        }

        return $this->uploadPkcs12Action($request, $id, $deviceVpnCertificateType->getId());
    }
}
