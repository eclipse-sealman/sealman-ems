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

namespace Tests\DataFixtures\DeviceCommunication\DeviceSecretsAndCertificates;

use App\DataFixtures\AbstractFixtureGroupAsClass;
use App\DataFixtures as ProdFixtures;
use App\DataFixtures\CertificateTypeFixtures;
use App\Entity\CertificateType;
use App\Entity\DeviceType;
use App\Entity\DeviceTypeCertificateType;
use App\Entity\DeviceTypeSecret;
use App\Enum\AuthenticationMethod;
use App\Enum\CertificateEncoding;
use App\Enum\CommunicationProcedure;
use App\Enum\ConfigFormat;
use App\Enum\DeviceTypeIcon;
use App\Enum\FieldRequirement;
use App\Enum\SecretValueBehaviour;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Builder\DeviceCommunicationTestMatrixBuilder;

class DeviceTypeCommunicationProcedureFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $deviceVpnCertificateType = $this->getReference(CertificateTypeFixtures::DEVICE_VPN_CERTIFICATE_TYPE, CertificateType::class);

        foreach (DeviceCommunicationTestMatrixBuilder::buildDeviceTypesSecretsCertificatesMatrix() as $deviceTypeConfiguration) {
            $deviceTypeName = $deviceTypeConfiguration[0]['deviceTypeName'];
            $deviceTypePrefix = $deviceTypeConfiguration[0]['deviceTypePrefix'];
            $secrets = $deviceTypeConfiguration[0]['secrets'];
            $certificates = $deviceTypeConfiguration[0]['certificates'];

            $deviceType = $this->getDeviceTypeStandardConfiguration($deviceTypePrefix, $deviceTypeName);
            if ($secrets) {
                $this->addDeviceTypeSecrets($manager, $deviceType);
            }
            if ($certificates) {
                $this->addDeviceTypeCertificates($manager, $deviceType);
            } else {
                if ('vcc' === $deviceTypePrefix) {
                    // DEVICE_VPN_CERTIFICATE_TYPE is required for VCC to make it available
                    $this->addDeviceTypeCertificateType($manager, $deviceType, $deviceVpnCertificateType, true);
                }
                if ('edge-gateway-vcc' === $deviceTypePrefix) {
                    // DEVICE_VPN_CERTIFICATE_TYPE is required for EG with VCC to make it available
                    $this->addDeviceTypeCertificateType($manager, $deviceType, $deviceVpnCertificateType, true);
                }
            }
            $manager->persist($deviceType);
            $manager->flush();

            $this->addReference('DeviceType-'.$deviceTypeName, $deviceType);
        }
    }

    // Use name that can be used in deviceName, certificateCommonNamePrefix, routePrefix
    protected function getDeviceTypeStandardConfiguration(string $deviceTypePrefix, string $name): DeviceType
    {
        $deviceType = new DeviceType();

        $deviceType->setName($name);
        $deviceType->setDeviceName('TestDevice');
        $deviceType->setCertificateCommonNamePrefix($name);
        $deviceType->setIcon(DeviceTypeIcon::ROUTER);
        $deviceType->setColor('#89E10A');
        $deviceType->setEnabled(true);
        $deviceType->setAuthenticationMethod(AuthenticationMethod::NONE);
        $deviceType->setCredentialsSource(null);
        $deviceType->setDeviceTypeSecretCredential(null);
        $deviceType->setRoutePrefix('/testdevice/'.$name);
        $deviceType->setVirtualSubnetCidr(30);
        $deviceType->setEnableConnectionAggregation(true);

        switch ($deviceTypePrefix) {
            case 'edge-gateway':
                $deviceType->setCommunicationProcedure(CommunicationProcedure::EDGEGATEWAY);
                $deviceType->setHasFirmware1(true);
                $deviceType->setNameFirmware1('Firmware');
                $deviceType->setHasConfig1(true);
                $deviceType->setNameConfig1('Config');
                $deviceType->setFormatConfig1(ConfigFormat::JSON);
                $deviceType->setHasTemplates(true);
                $deviceType->setHasCertificates(true);
                $deviceType->setHasVpn(true);
                $deviceType->setHasVariables(true);
                $deviceType->setHasGsm(true);
                $deviceType->setHasRequestConfig(true);
                $deviceType->setFieldSerialNumber(FieldRequirement::REQUIRED);
                $deviceType->setFieldModel(FieldRequirement::OPTIONAL);
                $deviceType->setFieldRegistrationId(FieldRequirement::OPTIONAL);
                $deviceType->setFieldEndorsementKey(FieldRequirement::OPTIONAL);
                $deviceType->setFieldHardwareVersion(FieldRequirement::OPTIONAL);
                $deviceType->setHasDeviceCommands(true);
                $deviceType->setDeviceCommandMaxRetries(3);
                break;

            case 'edge-gateway-vcc':
                $deviceType->setCommunicationProcedure(CommunicationProcedure::EDGEGATEWAY_WITH_VPNCONTAINERCLIENT);
                $deviceType->setHasFirmware1(true);
                $deviceType->setNameFirmware1('Firmware');
                $deviceType->setHasConfig1(true);
                $deviceType->setNameConfig1('Config');
                $deviceType->setFormatConfig1(ConfigFormat::JSON);
                $deviceType->setHasTemplates(true);
                $deviceType->setHasCertificates(true);
                $deviceType->setHasVpn(true);
                $deviceType->setHasVariables(true);
                $deviceType->setHasGsm(true);
                $deviceType->setHasRequestConfig(true);
                $deviceType->setFieldSerialNumber(FieldRequirement::REQUIRED);
                $deviceType->setFieldModel(FieldRequirement::OPTIONAL);
                $deviceType->setFieldRegistrationId(FieldRequirement::OPTIONAL);
                $deviceType->setFieldEndorsementKey(FieldRequirement::OPTIONAL);
                $deviceType->setFieldHardwareVersion(FieldRequirement::OPTIONAL);
                $deviceType->setHasDeviceCommands(true);
                $deviceType->setDeviceCommandMaxRetries(3);
                $deviceType->setHasEndpointDevices(true);
                $deviceType->setHasMasquerade(true);
                break;

            case 'vcc':
                $deviceType->setCommunicationProcedure(CommunicationProcedure::VPNCONTAINERCLIENT);
                $deviceType->setHasTemplates(true);
                $deviceType->setHasCertificates(true);
                $deviceType->setHasVpn(true);
                $deviceType->setHasVariables(true);
                $deviceType->setHasEndpointDevices(true);
                $deviceType->setHasMasquerade(true);
                $deviceType->setFieldSerialNumber(FieldRequirement::OPTIONAL);
                break;

            case 'tk800':
                $deviceType->setCommunicationProcedure(CommunicationProcedure::ROUTER);
                $deviceType->setHasFirmware1(true);
                $deviceType->setNameFirmware1('Firmware');
                $deviceType->setHasConfig1(true);
                $deviceType->setNameConfig1('Startup config');
                $deviceType->setHasConfig2(true);
                $deviceType->setHasAlwaysReinstallConfig2(true);
                $deviceType->setNameConfig2('Running config');
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
                $deviceType->setConfigMinRsrp(-116);
                $deviceType->setEnableFirmwareMinRsrp(true);
                $deviceType->setFirmwareMinRsrp(-116);
                $deviceType->setFormatConfig1(ConfigFormat::PLAIN);
                $deviceType->setFormatConfig2(ConfigFormat::PLAIN);
                break;

            case 'tk600':
                $deviceType->setCommunicationProcedure(CommunicationProcedure::ROUTER_DSA);
                $deviceType->setHasFirmware1(true);
                $deviceType->setNameFirmware1('Firmware');
                $deviceType->setHasFirmware2(true);
                $deviceType->setNameFirmware2('Device supervisor agent package');
                $deviceType->setHasFirmware3(true);
                $deviceType->setNameFirmware3('Device supervisor PySDK package');
                $deviceType->setHasConfig1(true);
                $deviceType->setNameConfig1('Startup config');
                $deviceType->setHasConfig2(true);
                $deviceType->setHasAlwaysReinstallConfig2(true);
                $deviceType->setNameConfig2('Running config');
                $deviceType->setHasConfig3(true);
                $deviceType->setNameConfig3('Device supervisor Config');
                $deviceType->setHasAlwaysReinstallConfig3(true);
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
                $deviceType->setConfigMinRsrp(-116);
                $deviceType->setEnableFirmwareMinRsrp(true);
                $deviceType->setFirmwareMinRsrp(-116);
                $deviceType->setFormatConfig1(ConfigFormat::PLAIN);
                $deviceType->setFormatConfig2(ConfigFormat::PLAIN);
                $deviceType->setFormatConfig3(ConfigFormat::JSON);
                break;

            case 'tk500':
                $deviceType->setCommunicationProcedure(CommunicationProcedure::ROUTER_ONE_CONFIG);
                $deviceType->setHasFirmware1(true);
                $deviceType->setNameFirmware1('Firmware');
                $deviceType->setHasConfig1(true);
                $deviceType->setNameConfig1('Startup config');
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
                $deviceType->setConfigMinRsrp(-116);
                $deviceType->setEnableFirmwareMinRsrp(true);
                $deviceType->setFirmwareMinRsrp(-116);
                $deviceType->setFormatConfig1(ConfigFormat::PLAIN);
                break;

            case 'tk100':
                $deviceType->setCommunicationProcedure(CommunicationProcedure::ROUTER_ONE_CONFIG);
                $deviceType->setHasFirmware1(true);
                $deviceType->setNameFirmware1('Firmware');
                $deviceType->setHasConfig1(true);
                $deviceType->setNameConfig1('Startup config');
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
                $deviceType->setConfigMinRsrp(-116);
                $deviceType->setEnableFirmwareMinRsrp(true);
                $deviceType->setFirmwareMinRsrp(-116);
                $deviceType->setFormatConfig1(ConfigFormat::PLAIN);
                break;

            case 'tk500v2':
                $deviceType->setCommunicationProcedure(CommunicationProcedure::ROUTER_ONE_CONFIG);
                $deviceType->setHasFirmware1(true);
                $deviceType->setNameFirmware1('Firmware');
                $deviceType->setHasConfig1(true);
                $deviceType->setNameConfig1('Startup config');
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
                $deviceType->setConfigMinRsrp(-116);
                $deviceType->setEnableFirmwareMinRsrp(true);
                $deviceType->setFirmwareMinRsrp(-116);
                $deviceType->setFormatConfig1(ConfigFormat::PLAIN);
                break;

            case 'sg-gateway':
                $deviceType->setCommunicationProcedure(CommunicationProcedure::SGGATEWAY);
                $deviceType->setHasFirmware1(true);
                $deviceType->setNameFirmware1('Firmware');
                $deviceType->setHasConfig1(true);
                $deviceType->setNameConfig1('Config');
                $deviceType->setFormatConfig1(ConfigFormat::JSON);
                $deviceType->setHasTemplates(true);
                $deviceType->setHasVariables(true);
                $deviceType->setFieldSerialNumber(FieldRequirement::REQUIRED);
                $deviceType->setFieldModel(FieldRequirement::OPTIONAL);
                $deviceType->setEnableFirmwareMinRsrp(false);
                $deviceType->setFirmwareMinRsrp(-116);
                break;

            case 'flex-edge':
                $deviceType->setCommunicationProcedure(CommunicationProcedure::FLEXEDGE);
                $deviceType->setHasFirmware1(true);
                $deviceType->setNameFirmware1('Firmware');
                $deviceType->setHasConfig1(true);
                $deviceType->setNameConfig1('Config');
                $deviceType->setHasCertificates(true);
                $deviceType->setHasVpn(true);
                $deviceType->setHasEndpointDevices(true);
                $deviceType->setHasTemplates(true);
                $deviceType->setHasVariables(true);
                $deviceType->setFieldSerialNumber(FieldRequirement::REQUIRED);
                $deviceType->setFieldModel(FieldRequirement::REQUIRED_IN_COMMUNICATION);
                $deviceType->setFormatConfig1(ConfigFormat::JSON);
                break;

            case 'tk500v3':
                $deviceType->setCommunicationProcedure(CommunicationProcedure::ROUTER_ONE_CONFIG);
                $deviceType->setHasFirmware1(true);
                $deviceType->setNameFirmware1('Firmware');
                $deviceType->setHasConfig1(true);
                $deviceType->setNameConfig1('Startup config');
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
                $deviceType->setConfigMinRsrp(-116);
                $deviceType->setEnableFirmwareMinRsrp(true);
                $deviceType->setFirmwareMinRsrp(-116);
                $deviceType->setFormatConfig1(ConfigFormat::PLAIN);
                break;
        }

        return $deviceType;
    }

    protected function addDeviceTypeCertificates(ObjectManager $manager, DeviceType $deviceType): void
    {
        $deviceVpnCertificateType = $this->getReference(CertificateTypeFixtures::DEVICE_VPN_CERTIFICATE_TYPE, CertificateType::class);
        $pkiCertificateType = $this->getReference(CertificateTypeCommunicationProcedureFixtures::PKI_CERTIFICATE_TYPE, CertificateType::class);
        $pkiCertificateType2 = $this->getReference(CertificateTypeCommunicationProcedureFixtures::PKI_CERTIFICATE_TYPE_2, CertificateType::class);
        $noPkiCertificateType = $this->getReference(CertificateTypeCommunicationProcedureFixtures::NO_PKI_CERTIFICATE_TYPE, CertificateType::class);

        $this->addDeviceTypeCertificateType($manager, $deviceType, $deviceVpnCertificateType, true);
        $this->addDeviceTypeCertificateType($manager, $deviceType, $pkiCertificateType, true);
        $this->addDeviceTypeCertificateType($manager, $deviceType, $pkiCertificateType2, false);
        $this->addDeviceTypeCertificateType($manager, $deviceType, $noPkiCertificateType, true);
    }

    protected function addDeviceTypeCertificateType(ObjectManager $manager, DeviceType $deviceType, CertificateType $certificateType, bool $autoRenewEnabled, CertificateEncoding $certificateEncoding = CertificateEncoding::HEX)
    {
        $deviceTypeCertificateType = new DeviceTypeCertificateType();
        $deviceTypeCertificateType->setDeviceType($deviceType);
        $deviceTypeCertificateType->setCertificateType($certificateType);
        $deviceTypeCertificateType->setEnableCertificatesAutoRenew($autoRenewEnabled);
        $deviceTypeCertificateType->setCertificatesAutoRenewDaysBefore(14);
        $deviceTypeCertificateType->setCertificateEncoding($certificateEncoding);

        $deviceType->addCertificateType($deviceTypeCertificateType);
        $manager->persist($deviceTypeCertificateType);
    }

    protected function addDeviceTypeSecrets(ObjectManager $manager, DeviceType $deviceType): void
    {
        $this->addDeviceTypeSecret($manager, $deviceType, 'renew', true, SecretValueBehaviour::RENEW);
        $this->addDeviceTypeSecret($manager, $deviceType, 'generate_renew', true, SecretValueBehaviour::GENERATE_RENEW);
        $this->addDeviceTypeSecret($manager, $deviceType, 'generate', true, SecretValueBehaviour::GENERATE);
        $this->addDeviceTypeSecret($manager, $deviceType, 'none', true, SecretValueBehaviour::NONE);
        $this->addDeviceTypeSecret($manager, $deviceType, 'renew_noVars', false, SecretValueBehaviour::RENEW);
        $this->addDeviceTypeSecret($manager, $deviceType, 'generate_renew_noVars', false, SecretValueBehaviour::GENERATE_RENEW);
        $this->addDeviceTypeSecret($manager, $deviceType, 'generate_noVars', false, SecretValueBehaviour::GENERATE);
        $this->addDeviceTypeSecret($manager, $deviceType, 'none_noVars', false, SecretValueBehaviour::NONE);
    }

    protected function addDeviceTypeSecret(ObjectManager $manager, DeviceType $deviceType, string $name, bool $useAsVariable, ?SecretValueBehaviour $secretValueBehaviour): void
    {
        $deviceTypeSecret = new DeviceTypeSecret();
        $deviceTypeSecret->setDeviceType($deviceType);
        $deviceTypeSecret->setName($name);
        $deviceTypeSecret->setUseAsVariable($useAsVariable);
        $deviceTypeSecret->setVariableNamePrefix($name);
        $deviceTypeSecret->setDescription($name.'_description');
        $deviceTypeSecret->setSecretValueBehaviour($secretValueBehaviour);
        $deviceTypeSecret->setSecretValueRenewAfterDays(10);

        $deviceType->addDeviceTypeSecret($deviceTypeSecret);
        $manager->persist($deviceTypeSecret);
    }

    public function getDependencies(): array
    {
        return [
            ProdFixtures\UserFixtures::class,
            ProdFixtures\DeviceTypeFixtures::class,
            TestFixtures\DeviceCommunication\DeviceSecretsAndCertificates\CertificateTypeCommunicationProcedureFixtures::class,
        ];
    }
}
