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

namespace App\Controller;

use App\Entity\Firmware;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// This controllers handles firmware download security while device is downloading firmware - only ROLE_DEVICE_* permissions or public access
class DeviceDownloadSecurityFirmwareController extends AbstractController
{
    use EntityManagerTrait;
    use DeviceCommunicationFactoryTrait;

    public function checkAuthAction(Request $request)
    {
        $downloadFirmwareUrlModel = $this->deviceCommunicationFactory->parseDownloadFirmwareUri($request);
        if (null === $downloadFirmwareUrlModel) {
            return $this->unauthorized();
        }

        $deviceType = $this->deviceCommunicationFactory->getDeviceTypeBySlug($downloadFirmwareUrlModel->getDeviceTypeSlug());
        if (!$deviceType) {
            return $this->unauthorized();
        }

        $deviceCommunication = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($deviceType);
        if (!$deviceCommunication) {
            return $this->unauthorized();
        }

        if ($deviceCommunication->isFirmwareSecured() && !$this->isGranted('ROLE_DEVICE_TYPE_'.$deviceType->getId())) {
            return $this->unauthorized();
        }

        $queryBuilder = $this->getRepository(Firmware::class)->createQueryBuilder('f');
        $queryBuilder->andWhere('f.uuid = :uuid');
        $queryBuilder->setParameter('uuid', $downloadFirmwareUrlModel->getFirmwareUuid());
        $queryBuilder->andWhere('f.secret = :secret');
        $queryBuilder->setParameter('secret', $downloadFirmwareUrlModel->getFirmwareSecret());
        $queryBuilder->andWhere('f.filename = :filename');
        $queryBuilder->setParameter('filename', $downloadFirmwareUrlModel->getFirmwareFilename());
        $queryBuilder->andWhere('f.deviceType = :deviceType');
        $queryBuilder->setParameter('deviceType', $deviceType);
        $queryBuilder->setMaxResults(1);

        $firmware = $queryBuilder->getQuery()->getOneOrNullResult();

        if (!$firmware) {
            return $this->unauthorized();
        }

        // Using legacy uuid as folder name if exists (meaning firmware was created before v3.3.0)
        $folderName = $firmware->getLegacyUuid() ? $firmware->getLegacyUuid() : $firmware->getUuid();
        $firmwareFilepath = $downloadFirmwareUrlModel->getDeviceTypeSlug().'/'.$folderName.'/'.$firmware->getFilename();

        return new Response(null, Response::HTTP_NO_CONTENT, ['FIRMWARE-FILEPATH' => $firmwareFilepath]);
    }

    protected function unauthorized(): Response
    {
        return new Response(null, Response::HTTP_UNAUTHORIZED);
    }
}
