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

use App\Entity\DeviceType;
use App\Entity\Firmware;
use App\Entity\FirmwareHardwareFile;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This controllers handles firmware and firmware hardware file download security while user is using webUI download button.
 */
class DownloadSecurityFirmwareController extends AbstractController
{
    use EntityManagerTrait;
    use DeviceCommunicationFactoryTrait;
    use SecurityHelperTrait;

    public const ENTITY_FIRMWARE = 'firmware';
    public const ENTITY_FIRMWAREHARDWAREFILE = 'firmwareHardwareFile';

    public function checkAuthAction(Request $request)
    {
        $uri = $request->headers->get('X-Original-URI');
        if (!$uri) {
            return $this->unauthorized();
        }

        $parsedUri = $this->parseUri($uri);
        if (null === $parsedUri) {
            return $this->unauthorized();
        }

        [$entity, $deviceTypeSlug, $uuid, $filename] = $parsedUri;

        $deviceType = $this->deviceCommunicationFactory->getDeviceTypeBySlug($deviceTypeSlug);
        if (!$deviceType) {
            return $this->unauthorized();
        }

        switch ($entity) {
            case self::ENTITY_FIRMWARE:
                return $this->getFirmwareResponse($deviceType, $uuid, $filename);
            case self::ENTITY_FIRMWAREHARDWAREFILE:
                return $this->getFirmwareHardwareFileResponse($deviceType, $uuid, $filename);
        }

        return $this->unauthorized();
    }

    protected function getFirmwareResponse(DeviceType $deviceType, string $uuid, string $filename): Response
    {
        $queryBuilder = $this->getRepository(Firmware::class)->createQueryBuilder('f');
        $queryBuilder->andWhere('f.uuid = :uuid');
        $queryBuilder->setParameter('uuid', $uuid);
        $queryBuilder->andWhere('f.filename = :filename');
        $queryBuilder->setParameter('filename', $filename);
        $queryBuilder->andWhere('f.deviceType = :deviceType');
        $queryBuilder->setParameter('deviceType', $deviceType);
        $queryBuilder->setMaxResults(1);

        // Add user security for query - same as for showing firmware in webUI list
        $this->applyUserAccessTagsQueryModificationForTemplateComponents($queryBuilder, 'f');

        $firmware = $queryBuilder->getQuery()->getOneOrNullResult();

        if (!$firmware) {
            return $this->unauthorized();
        }

        // Using legacy uuid as folder name if exists (meaning firmware was created before v3.3.0)
        $folderName = $firmware->getLegacyUuid() ? $firmware->getLegacyUuid() : $firmware->getUuid();
        $firmwareFilepath = '/firmware/'.$deviceType->getSlug().'/'.$folderName.'/'.$firmware->getFilename();

        return new Response(null, Response::HTTP_NO_CONTENT, ['FIRMWARE-FILEPATH' => $firmwareFilepath]);
    }

    protected function getFirmwareHardwareFileResponse(DeviceType $deviceType, string $uuid, string $filename): Response
    {
        $queryBuilder = $this->getRepository(FirmwareHardwareFile::class)->createQueryBuilder('fhf');
        $queryBuilder->leftJoin('fhf.firmware', 'f');
        $queryBuilder->andWhere('f.uuid = :uuid');
        $queryBuilder->setParameter('uuid', $uuid);
        $queryBuilder->andWhere('f.deviceType = :deviceType');
        $queryBuilder->setParameter('deviceType', $deviceType);
        $queryBuilder->andWhere('fhf.filename = :filename');
        $queryBuilder->setParameter('filename', $filename);
        $queryBuilder->setMaxResults(1);

        // Add user security for query - same as for showing firmware in webUI list
        $this->applyUserAccessTagsQueryModificationForTemplateComponents($queryBuilder, 'f');

        $firmwareHardwareFile = $queryBuilder->getQuery()->getOneOrNullResult();

        if (!$firmwareHardwareFile) {
            return $this->unauthorized();
        }

        // Using legacy uuid as folder name if exists (meaning firmware was created before v3.3.0)
        $firmware = $firmwareHardwareFile->getFirmware();
        $folderName = $firmware->getLegacyUuid() ? $firmware->getLegacyUuid() : $firmware->getUuid();
        $firmwareFilepath = '/firmwarehardwarefile/'.$deviceType->getSlug().'/'.$folderName.'/'.$firmwareHardwareFile->getFilename();

        return new Response(null, Response::HTTP_NO_CONTENT, ['FIRMWARE-FILEPATH' => $firmwareFilepath]);
    }

    /**
     * Parse $uri. When a valid $uri is passed function will return an array:
     * [
     *  $entity, (one of self::ENTITY_FIRMWARE, self::ENTITY_FIRMWAREHARDWAREFILE),
     *  $deviceTypeSlug,
     *  $uuid,
     *  $filename
     * ].
     *
     * Invalid $uri will return null.
     */
    protected function parseUri(string $uri): ?array
    {
        // $uri is expected to be structured one of following ways:
        // /web/api/download/firmware/DEVICE_TYPE_SLUG/UUID/FILENAME
        // /web/api/download/firmwarehardwarefile/DEVICE_TYPE_SLUG/UUID/FILENAME

        $prefixes = [
            self::ENTITY_FIRMWARE => '/web/api/download/firmware/',
            self::ENTITY_FIRMWAREHARDWAREFILE => '/web/api/download/firmwarehardwarefile/',
        ];

        foreach ($prefixes as $entity => $prefix) {
            if (!str_starts_with($uri, $prefix)) {
                continue;
            }

            $uriParts = explode('/', substr($uri, \strlen($prefix)));

            if (3 != count($uriParts)) {
                return null;
            }

            if (!$uriParts[0] || !$uriParts[1] || !$uriParts[2]) {
                return null;
            }

            return [$entity, $uriParts[0], $uriParts[1], $uriParts[2]];
        }

        return null;
    }

    protected function unauthorized(): Response
    {
        return new Response(null, Response::HTTP_UNAUTHORIZED);
    }
}
