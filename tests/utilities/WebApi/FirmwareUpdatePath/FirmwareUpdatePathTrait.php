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

namespace Tests\Utilities\WebApi\FirmwareUpdatePath;

use App\Entity\DeviceType;
use App\Entity\User;
use App\Enum\Feature;
use App\Enum\FirmwareVersionSchema;

/**
 * Separate trait with actions that handle Firmware Update Path endpoints for readability.
 */
trait FirmwareUpdatePathTrait
{
    // TODO use BUILDER when ready
    public static function featuresProvider(): array
    {
        $testCases = [];
        foreach (Feature::cases() as $feature) {
            $testCases[$feature->name] = [$feature->value];
        }

        return $testCases;
    }

    // TODO use BUILDER when ready
    public static function featuresAndFirmwareSchemaProvider(): array
    {
        $testCases = [];
        foreach (Feature::cases() as $feature) {
            foreach (FirmwareVersionSchema::cases() as $firmwareSchema) {
                $testCases[$feature->name.'-'.$firmwareSchema->name] = [$feature->value, $firmwareSchema];
            }
        }

        return $testCases;
    }

    protected function getOtherFeature(string $currentFeature): string
    {
        foreach (Feature::cases() as $feature) {
            if ($feature->value !== $currentFeature) {
                return $feature->value;
            }
        }

        throw new \InvalidArgumentException('No other feature found for '.$currentFeature);
    }

    protected function getValidFirmwareVersionByLevel(FirmwareVersionSchema $schema, int $level): string
    {
        $firmwareVersionsArray = [
            FirmwareVersionSchema::ANY_SCHEMA->value => ['0', 'a', 'ab', 'abc', 'abcd', 'abcde', 'abcdef'],
            FirmwareVersionSchema::SEMANTIC_VERSIONING->value => ['0.0.1', '1.0.0', '1.0.1', '1.1.0', '1.1.1', '1.2.0', '1.2.1'],
            FirmwareVersionSchema::V_SEMANTIC_VERSIONING->value => ['v0.0.1', 'v1.0.0', 'v1.0.1', 'v1.1.0', 'v1.1.1', 'v1.2.0', 'v1.2.1'],
            FirmwareVersionSchema::EG_OS_SCHEMA->value => ['0.0.1', '1.0.0', '1.0.1', '1.1.0_alpha', '1.1.0', '1.2.0_rc1', '1.2.1'],
        ];
        if ($level < 0 || $level > count($firmwareVersionsArray[$schema->value])) {
            throw new \InvalidArgumentException('Level '.$level.' is out of range.');
        }

        return $firmwareVersionsArray[$schema->value][$level];
    }

    protected function getDeviceTypeEditValues(DeviceType $deviceType): array
    {
        return [
                'firmwareSchema1' => $deviceType->getFirmwareSchema1(),
                'firmwareSchema2' => $deviceType->getFirmwareSchema2(),
                'firmwareSchema3' => $deviceType->getFirmwareSchema3(),
                'enableFirmwareMinRsrp' => $deviceType->getEnableFirmwareMinRsrp(),
                'enableConfigMinRsrp' => $deviceType->getEnableConfigMinRsrp(),
                'name' => $deviceType->getName(),
                'icon' => $deviceType->getIcon(),
                'deviceName' => $deviceType->getDeviceName(),
                'certificateCommonNamePrefix' => $deviceType->getCertificateCommonNamePrefix(),
                'authenticationMethod' => $deviceType->getAuthenticationMethod(),
                'routePrefix' => $deviceType->getRoutePrefix(),
                'communicationProcedure' => $deviceType->getCommunicationProcedure(),
                'fieldSerialNumber' => $deviceType->getFieldSerialNumber(),
                'fieldImsi' => $deviceType->getFieldImsi(),
                'fieldModel' => $deviceType->getFieldModel(),
                'fieldRegistrationId' => $deviceType->getFieldRegistrationId(),
                'fieldEndorsementKey' => $deviceType->getFieldEndorsementKey(),
                'fieldHardwareVersion' => $deviceType->getFieldHardwareVersion(),
                'hasFirmware1' => $deviceType->getHasFirmware1(),
                'hasFirmware2' => $deviceType->getHasFirmware2(),
                'hasFirmware3' => $deviceType->getHasFirmware3(),
                'nameFirmware1' => $deviceType->getNameFirmware1(),
                'nameFirmware2' => $deviceType->getNameFirmware2(),
                'nameFirmware3' => $deviceType->getNameFirmware3(),
                'hasConfig1' => $deviceType->getHasConfig1(),
                'hasConfig2' => $deviceType->getHasConfig2(),
                'hasConfig3' => $deviceType->getHasConfig3(),
                'nameConfig1' => $deviceType->getNameConfig1(),
                'nameConfig2' => $deviceType->getNameConfig2(),
                'nameConfig3' => $deviceType->getNameConfig3(),
                'formatConfig1' => $deviceType->getFormatConfig1(),
                'formatConfig2' => $deviceType->getFormatConfig2(),
                'formatConfig3' => $deviceType->getFormatConfig3(),
                'hasAlwaysReinstallConfig2' => $deviceType->getHasAlwaysReinstallConfig2(),
                'hasTemplates' => $deviceType->getHasTemplates(),
                'hasGsm' => $deviceType->getHasGsm(),
                'credentialsSource' => $deviceType->getCredentialsSource(),
                'color' => $deviceType->getColor(),
                'configMinRsrp' => $deviceType->getConfigMinRsrp(),
                'firmwareMinRsrp' => $deviceType->getFirmwareMinRsrp(),
            ];
    }

    protected function getDeviceTypeLimitedEditValues(DeviceType $deviceType): array
    {
        $certificateTypes = [];
        foreach ($deviceType->getCertificateTypes() as $certificateType) {
            $certificateTypes[] = [
                'certificateEncoding' => $certificateType->getCertificateEncoding(),
                'certificateType' => $certificateType->getCertificateType()->getId(),
                'certificatesAutoRenewDaysBefore' => $certificateType->getCertificatesAutoRenewDaysBefore(),
                'enableCertificatesAutoRenew' => $certificateType->getEnableCertificatesAutoRenew(),
                'enableSubjectAltName' => $certificateType->getEnableSubjectAltName(),
            ];
        }

        return [
                'firmwareSchema1' => $deviceType->getFirmwareSchema1(),
                'firmwareSchema2' => $deviceType->getFirmwareSchema2(),
                'firmwareSchema3' => $deviceType->getFirmwareSchema3(),
                'enableFirmwareMinRsrp' => $deviceType->getEnableFirmwareMinRsrp(),
                'enableConfigMinRsrp' => $deviceType->getEnableConfigMinRsrp(),
                'name' => $deviceType->getName(),
                'icon' => $deviceType->getIcon(),
                'deviceName' => $deviceType->getDeviceName(),
                'certificateCommonNamePrefix' => $deviceType->getCertificateCommonNamePrefix(),
                'authenticationMethod' => $deviceType->getAuthenticationMethod(),
                'credentialsSource' => $deviceType->getCredentialsSource(),
                'color' => $deviceType->getColor(),
                'configMinRsrp' => $deviceType->getConfigMinRsrp(),
                'firmwareMinRsrp' => $deviceType->getFirmwareMinRsrp(),
                'virtualSubnetCidr' => $deviceType->getVirtualSubnetCidr(),
                'certificateTypes' => $certificateTypes,
            ];
    }

    /**
     * Helper function to update entity by id with new values.
     * Also manually sets createdBy and updatedBy to 'system' user. To avoid infinite loop in blameable listener in tests.
     */
    protected function updateEntityById(string $entityClass, int $id, array $newValues): void
    {
        $entity = $this->getRepository($entityClass)->find($id);
        $this->assertNotNull($entity);

        foreach ($newValues as $field => $value) {
            $setter = 'set'.ucfirst($field);
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            } else {
                throw new \InvalidArgumentException("Setter method {$setter} does not exist in class {$entityClass}");
            }
        }

        // This is stupid, but it works, so it is not stupid, but genius.
        // We need to update createdBy and updatedBy to another user so that blameable listener will not fall in infinite loop.
        // So every time other user is used.
        $user = $this->getRepository(User::class)->findOneBy(['username' => 'system']);
        if ($entity->getUpdatedBy() == $user) {
            $user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        }
        $entity->setCreatedBy($user);
        $entity->setUpdatedBy($user);

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
    }

    protected function createFirmwareUpdatePathChain($feature, FirmwareVersionSchema $firmwareSchema, array $firmwareLevels): array
    {
        $resultArray = [];
        $firmwareId = null;
        foreach ($firmwareLevels as $level) {
            $this->getApiClient()->request(
                uri: '/web/api/firmware/create',
                parameters: [
                    'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, $level),
                    'feature' => $feature,
                    'requiredFirmware' => $firmwareId,
                ]
            );
            $firmwareId = $this->getResponseValue('id');
            $this->assertNotNull($firmwareId);

            $resultArray[$level] = $firmwareId;
        }

        return $resultArray;
    }

    protected function createProductionTemplateWithFirmware(int $firmwareId, string $feature): int
    {
        $this->getApiClient()->request(
            uri: '/web/api/template/create',
        );

        $templateId = $this->getResponseValue('id');
        $this->assertNotNull($templateId);

        $this->getApiClient()->request(
            uri: '/web/api/templateversion/create/staging/{template}',
            parameters: [
                'firmware'.$feature => $firmwareId,
            ]
        );

        $this->getApiClient()->request(
            uri: '/web/api/templateversion/select/staging/{id}',
        );

        $this->getApiClient()->request(
            uri: '/web/api/templateversion/select/production/{id}',
        );

        return $templateId;
    }

    protected function createDeviceWithProductionTemplateWithFirmwareWithReinstallFlag(int $firmwareId, string $feature): int
    {
        $templateId = $this->createProductionTemplateWithFirmware($firmwareId, $feature);

        $this->getApiClient()->request(
            uri: '/web/api/device/create',
            parameters: [
                'template' => $templateId,
                'reinstallFirmware'.$feature => true,
                'enabled' => true,
            ]
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        return $deviceId;
    }
}
