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

namespace App\DataFixtures;

use App\Entity\CertificateType;
use App\Entity\DeviceType;
use App\Entity\DeviceTypeCertificateType;
use App\Enum\AuthenticationMethod;
use App\Enum\CertificateEncoding;
use App\Enum\CommunicationProcedure;
use App\Enum\ConfigFormat;
use App\Enum\CredentialsSource;
use App\Enum\DeviceTypeIcon;
use App\Enum\FieldRequirement;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DeviceTypeFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const DEVICETYPE_ROUTERTK800_REFERENCE = 'deviceType:router:tk800';
    public const DEVICETYPE_EDGEGATEWAY_REFERENCE = 'deviceType:edgeGateway';

    public function load(ObjectManager $manager): void
    {
        $deviceVpnCertificateType = $this->getReference(CertificateTypeFixtures::DEVICE_VPN_CERTIFICATE_TYPE, CertificateType::class);

        $deviceType = new DeviceType();
        $deviceType->setName('TK800');
        $deviceType->setDeviceName('Router');
        $deviceType->setCertificateCommonNamePrefix('tk800');
        $deviceType->setIcon(DeviceTypeIcon::ROUTER);
        $deviceType->setColor('#E10A1E');
        $deviceType->setHasFirmware1(true);
        $deviceType->setNameFirmware1('Firmware');
        $deviceType->setHasConfig1(true);
        $deviceType->setNameConfig1('Startup config');
        $deviceType->setHasConfig2(true);
        $deviceType->setHasAlwaysReinstallConfig2(true);
        $deviceType->setNameConfig2('Running config');
        $deviceType->setAuthenticationMethod(AuthenticationMethod::DIGEST);
        $deviceType->setCredentialsSource(CredentialsSource::USER);
        $deviceType->setRoutePrefix('/router/tk800');
        $deviceType->setCommunicationProcedure(CommunicationProcedure::ROUTER);
        $deviceType->setHasCertificates(true);
        $deviceType->setHasVpn(true);
        $deviceType->setHasEndpointDevices(true);
        $deviceType->setHasTemplates(true);
        $deviceType->setHasVariables(true);
        $deviceType->setHasGsm(true);
        $deviceType->setHasRequestDiagnose(true);
        $deviceType->setVirtualSubnetCidr(30);
        $deviceType->setFieldSerialNumber(FieldRequirement::REQUIRED);
        $deviceType->setFieldImsi(FieldRequirement::OPTIONAL);
        $deviceType->setFieldModel(FieldRequirement::OPTIONAL);
        $deviceType->setEnableConfigMinRsrp(true);
        $deviceType->setConfigMinRsrp(-116);
        $deviceType->setEnableFirmwareMinRsrp(true);
        $deviceType->setFirmwareMinRsrp(-116);
        $deviceType->setEnableConnectionAggregation(true);
        $deviceType->setFormatConfig1(ConfigFormat::PLAIN);
        $deviceType->setFormatConfig2(ConfigFormat::PLAIN);
        $this->addDeviceTypeCertificateType($manager, $deviceType, $deviceVpnCertificateType);
        $manager->persist($deviceType);

        $deviceTypeRouterTk800 = $deviceType;

        $deviceType = new DeviceType();
        $deviceType->setName('TK600');
        $deviceType->setDeviceName('Router');
        $deviceType->setCertificateCommonNamePrefix('tk600');
        $deviceType->setIcon(DeviceTypeIcon::ROUTER);
        $deviceType->setColor('#89E10A');
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
        $deviceType->setAuthenticationMethod(AuthenticationMethod::DIGEST);
        $deviceType->setCredentialsSource(CredentialsSource::USER);
        $deviceType->setRoutePrefix('/router/tk600');
        $deviceType->setCommunicationProcedure(CommunicationProcedure::ROUTER_DSA);
        $deviceType->setHasCertificates(true);
        $deviceType->setHasVpn(true);
        $deviceType->setHasEndpointDevices(true);
        $deviceType->setHasTemplates(true);
        $deviceType->setHasVariables(true);
        $deviceType->setHasGsm(true);
        $deviceType->setHasRequestDiagnose(true);
        $deviceType->setVirtualSubnetCidr(30);
        $deviceType->setFieldSerialNumber(FieldRequirement::REQUIRED);
        $deviceType->setFieldImsi(FieldRequirement::OPTIONAL);
        $deviceType->setFieldModel(FieldRequirement::OPTIONAL);
        $deviceType->setEnableConfigMinRsrp(true);
        $deviceType->setConfigMinRsrp(-116);
        $deviceType->setEnableFirmwareMinRsrp(true);
        $deviceType->setFirmwareMinRsrp(-116);
        $deviceType->setEnableConnectionAggregation(true);
        $deviceType->setFormatConfig1(ConfigFormat::PLAIN);
        $deviceType->setFormatConfig2(ConfigFormat::PLAIN);
        $deviceType->setFormatConfig3(ConfigFormat::JSON);
        $this->addDeviceTypeCertificateType($manager, $deviceType, $deviceVpnCertificateType);
        $manager->persist($deviceType);

        $deviceType = new DeviceType();
        $deviceType->setName('TK100');
        $deviceType->setDeviceName('Router');
        $deviceType->setCertificateCommonNamePrefix('tk100');
        $deviceType->setIcon(DeviceTypeIcon::ROUTER);
        $deviceType->setColor('#0AE1CD');
        $deviceType->setHasFirmware1(true);
        $deviceType->setNameFirmware1('Firmware');
        $deviceType->setHasConfig1(true);
        $deviceType->setNameConfig1('Startup config');
        $deviceType->setAuthenticationMethod(AuthenticationMethod::DIGEST);
        $deviceType->setCredentialsSource(CredentialsSource::USER);
        $deviceType->setRoutePrefix('/router/tk100');
        $deviceType->setCommunicationProcedure(CommunicationProcedure::ROUTER_ONE_CONFIG);
        $deviceType->setHasCertificates(true);
        $deviceType->setHasVpn(true);
        $deviceType->setHasEndpointDevices(true);
        $deviceType->setHasTemplates(true);
        $deviceType->setHasVariables(true);
        $deviceType->setHasGsm(true);
        $deviceType->setHasRequestDiagnose(true);
        $deviceType->setVirtualSubnetCidr(30);
        $deviceType->setFieldSerialNumber(FieldRequirement::REQUIRED);
        $deviceType->setFieldImsi(FieldRequirement::OPTIONAL);
        $deviceType->setFieldModel(FieldRequirement::OPTIONAL);
        $deviceType->setEnableConfigMinRsrp(true);
        $deviceType->setConfigMinRsrp(-116);
        $deviceType->setEnableFirmwareMinRsrp(true);
        $deviceType->setFirmwareMinRsrp(-116);
        $deviceType->setEnableConnectionAggregation(true);
        $deviceType->setFormatConfig1(ConfigFormat::PLAIN);
        $this->addDeviceTypeCertificateType($manager, $deviceType, $deviceVpnCertificateType, CertificateEncoding::ONE_LINE_PEM);
        $manager->persist($deviceType);

        $deviceType = new DeviceType();
        $deviceType->setName('TK500');
        $deviceType->setDeviceName('Router');
        $deviceType->setCertificateCommonNamePrefix('tk500');
        $deviceType->setIcon(DeviceTypeIcon::ROUTER);
        $deviceType->setColor('#620AE1');
        $deviceType->setHasFirmware1(true);
        $deviceType->setNameFirmware1('Firmware');
        $deviceType->setHasConfig1(true);
        $deviceType->setNameConfig1('Startup config');
        $deviceType->setAuthenticationMethod(AuthenticationMethod::DIGEST);
        $deviceType->setCredentialsSource(CredentialsSource::USER);
        $deviceType->setRoutePrefix('/router/tk500');
        $deviceType->setCommunicationProcedure(CommunicationProcedure::ROUTER_ONE_CONFIG);
        $deviceType->setHasCertificates(true);
        $deviceType->setHasVpn(true);
        $deviceType->setHasEndpointDevices(true);
        $deviceType->setHasTemplates(true);
        $deviceType->setHasVariables(true);
        $deviceType->setHasGsm(true);
        $deviceType->setHasRequestDiagnose(true);
        $deviceType->setVirtualSubnetCidr(30);
        $deviceType->setFieldSerialNumber(FieldRequirement::REQUIRED);
        $deviceType->setFieldImsi(FieldRequirement::OPTIONAL);
        $deviceType->setFieldModel(FieldRequirement::OPTIONAL);
        $deviceType->setEnableConfigMinRsrp(true);
        $deviceType->setConfigMinRsrp(-116);
        $deviceType->setEnableFirmwareMinRsrp(true);
        $deviceType->setFirmwareMinRsrp(-116);
        $deviceType->setEnableConnectionAggregation(true);
        $deviceType->setFormatConfig1(ConfigFormat::PLAIN);
        $this->addDeviceTypeCertificateType($manager, $deviceType, $deviceVpnCertificateType, CertificateEncoding::ONE_LINE_PEM);
        $manager->persist($deviceType);

        $deviceType = new DeviceType();
        $deviceType->setName('TK500v2');
        $deviceType->setDeviceName('Router');
        $deviceType->setCertificateCommonNamePrefix('tk5002');
        $deviceType->setIcon(DeviceTypeIcon::ROUTER);
        $deviceType->setColor('#0A1EE1');
        $deviceType->setHasFirmware1(true);
        $deviceType->setNameFirmware1('Firmware');
        $deviceType->setHasConfig1(true);
        $deviceType->setNameConfig1('Startup config');
        $deviceType->setAuthenticationMethod(AuthenticationMethod::DIGEST);
        $deviceType->setCredentialsSource(CredentialsSource::USER);
        $deviceType->setRoutePrefix('/router/tk500-v2');
        $deviceType->setCommunicationProcedure(CommunicationProcedure::ROUTER_ONE_CONFIG);
        $deviceType->setHasCertificates(true);
        $deviceType->setHasVpn(true);
        $deviceType->setHasEndpointDevices(true);
        $deviceType->setHasTemplates(true);
        $deviceType->setHasVariables(true);
        $deviceType->setHasGsm(true);
        $deviceType->setHasRequestDiagnose(true);
        $deviceType->setVirtualSubnetCidr(30);
        $deviceType->setFieldSerialNumber(FieldRequirement::REQUIRED);
        $deviceType->setFieldImsi(FieldRequirement::OPTIONAL);
        $deviceType->setFieldModel(FieldRequirement::OPTIONAL);
        $deviceType->setEnableConfigMinRsrp(true);
        $deviceType->setConfigMinRsrp(-116);
        $deviceType->setEnableFirmwareMinRsrp(true);
        $deviceType->setFirmwareMinRsrp(-116);
        $deviceType->setEnableConnectionAggregation(true);
        $deviceType->setFormatConfig1(ConfigFormat::PLAIN);
        $this->addDeviceTypeCertificateType($manager, $deviceType, $deviceVpnCertificateType, CertificateEncoding::ONE_LINE_PEM);
        $manager->persist($deviceType);

        $deviceType = new DeviceType();
        $deviceType->setName('TK500v3');
        $deviceType->setDeviceName('Router');
        $deviceType->setCertificateCommonNamePrefix('tk5003');
        $deviceType->setIcon(DeviceTypeIcon::ROUTER);
        $deviceType->setColor('#AF0AE1');
        $deviceType->setHasFirmware1(true);
        $deviceType->setNameFirmware1('Firmware');
        $deviceType->setHasConfig1(true);
        $deviceType->setNameConfig1('Startup config');
        $deviceType->setAuthenticationMethod(AuthenticationMethod::DIGEST);
        $deviceType->setRoutePrefix('/router/tk500-v3');
        $deviceType->setCommunicationProcedure(CommunicationProcedure::ROUTER_ONE_CONFIG);
        $deviceType->setHasCertificates(true);
        $deviceType->setHasVpn(true);
        $deviceType->setHasEndpointDevices(true);
        $deviceType->setHasTemplates(true);
        $deviceType->setHasVariables(true);
        $deviceType->setHasGsm(true);
        $deviceType->setHasRequestDiagnose(true);
        $deviceType->setVirtualSubnetCidr(30);
        $deviceType->setFieldSerialNumber(FieldRequirement::REQUIRED);
        $deviceType->setFieldImsi(FieldRequirement::OPTIONAL);
        $deviceType->setFieldModel(FieldRequirement::OPTIONAL);
        $deviceType->setEnableConfigMinRsrp(true);
        $deviceType->setConfigMinRsrp(-116);
        $deviceType->setEnableFirmwareMinRsrp(true);
        $deviceType->setFirmwareMinRsrp(-116);
        $deviceType->setEnableConnectionAggregation(true);
        $deviceType->setFormatConfig1(ConfigFormat::PLAIN);
        $this->addDeviceTypeCertificateType($manager, $deviceType, $deviceVpnCertificateType, CertificateEncoding::ONE_LINE_PEM);
        $manager->persist($deviceType);

        $deviceType = new DeviceType();
        $deviceType->setName('Flex edge device');
        $deviceType->setDeviceName('Flex edge device');
        $deviceType->setCertificateCommonNamePrefix('fe');
        $deviceType->setIcon(DeviceTypeIcon::HUB);
        $deviceType->setColor('#0A1EE1');
        $deviceType->setEnabled(false);
        $deviceType->setHasFirmware1(true);
        $deviceType->setNameFirmware1('Firmware');
        $deviceType->setHasConfig1(true);
        $deviceType->setNameConfig1('Config');
        $deviceType->setAuthenticationMethod(AuthenticationMethod::NONE);
        $deviceType->setRoutePrefix('/');
        $deviceType->setCommunicationProcedure(CommunicationProcedure::FLEXEDGE);
        $deviceType->setHasCertificates(true);
        $deviceType->setHasVpn(true);
        $deviceType->setHasEndpointDevices(true);
        $deviceType->setHasTemplates(true);
        $deviceType->setHasVariables(true);
        $deviceType->setVirtualSubnetCidr(30);
        $deviceType->setFieldSerialNumber(FieldRequirement::REQUIRED);
        $deviceType->setFieldModel(FieldRequirement::REQUIRED_IN_COMMUNICATION);
        $deviceType->setEnableConnectionAggregation(true);
        $deviceType->setFormatConfig1(ConfigFormat::JSON);
        $this->addDeviceTypeCertificateType($manager, $deviceType, $deviceVpnCertificateType);
        $manager->persist($deviceType);

        $deviceType = new DeviceType();
        $deviceType->setName('SG-gateway');
        $deviceType->setDeviceName('SG-gateway');
        $deviceType->setCertificateCommonNamePrefix('sg');
        $deviceType->setIcon(DeviceTypeIcon::SETTINGSINPUTANTENNA);
        $deviceType->setColor('#40474D');
        $deviceType->setEnabled(false);
        $deviceType->setHasFirmware1(true);
        $deviceType->setNameFirmware1('Firmware');
        $deviceType->setHasConfig1(true);
        $deviceType->setNameConfig1('Config');
        $deviceType->setFormatConfig1(ConfigFormat::JSON);
        $deviceType->setAuthenticationMethod(AuthenticationMethod::BASIC);
        $deviceType->setCredentialsSource(CredentialsSource::USER);
        $deviceType->setRoutePrefix('/api/hms');
        $deviceType->setCommunicationProcedure(CommunicationProcedure::SGGATEWAY);
        $deviceType->setHasTemplates(true);
        $deviceType->setHasVariables(true);
        $deviceType->setFieldSerialNumber(FieldRequirement::REQUIRED);
        $deviceType->setFieldModel(FieldRequirement::OPTIONAL);
        $deviceType->setEnableFirmwareMinRsrp(false);
        $deviceType->setFirmwareMinRsrp(-116);
        $deviceType->setEnableConnectionAggregation(true);
        $manager->persist($deviceType);

        $deviceType = new DeviceType();
        $deviceType->setName('VPN Container Client');
        $deviceType->setDeviceName('VPN Container Client');
        $deviceType->setCertificateCommonNamePrefix('vcc');
        $deviceType->setIcon(DeviceTypeIcon::CLOUDSYNC);
        $deviceType->setColor('#E5961A');
        $deviceType->setAuthenticationMethod(AuthenticationMethod::BASIC);
        $deviceType->setCredentialsSource(CredentialsSource::USER);
        $deviceType->setRoutePrefix('/api/vpncontainerclient');
        $deviceType->setCommunicationProcedure(CommunicationProcedure::VPNCONTAINERCLIENT);
        $deviceType->setHasTemplates(true);
        $deviceType->setHasCertificates(true);
        $deviceType->setHasVpn(true);
        $deviceType->setHasVariables(true);
        $deviceType->setHasEndpointDevices(true);
        $deviceType->setHasMasquerade(true);
        $deviceType->setVirtualSubnetCidr(30);
        $deviceType->setFieldSerialNumber(FieldRequirement::OPTIONAL);
        $deviceType->setEnableConnectionAggregation(true);
        $this->addDeviceTypeCertificateType($manager, $deviceType, $deviceVpnCertificateType);
        $manager->persist($deviceType);

        $deviceTypeEdgeGateway = new DeviceType();
        $deviceTypeEdgeGateway->setName('Edge gateway');
        $deviceTypeEdgeGateway->setDeviceName('Edge gateway');
        $deviceTypeEdgeGateway->setCertificateCommonNamePrefix('eg');
        $deviceTypeEdgeGateway->setIcon(DeviceTypeIcon::COMPUTER);
        $deviceTypeEdgeGateway->setColor('#00B6FF');
        $deviceTypeEdgeGateway->setHasFirmware1(true);
        $deviceTypeEdgeGateway->setNameFirmware1('Firmware');
        $deviceTypeEdgeGateway->setHasConfig1(true);
        $deviceTypeEdgeGateway->setNameConfig1('Config');
        $deviceTypeEdgeGateway->setFormatConfig1(ConfigFormat::JSON);
        $deviceTypeEdgeGateway->setAuthenticationMethod(AuthenticationMethod::BASIC);
        $deviceTypeEdgeGateway->setCredentialsSource(CredentialsSource::USER);
        $deviceTypeEdgeGateway->setRoutePrefix('/api/edgegateway');
        $deviceTypeEdgeGateway->setCommunicationProcedure(CommunicationProcedure::EDGEGATEWAY);
        $deviceTypeEdgeGateway->setHasTemplates(true);
        $deviceTypeEdgeGateway->setHasCertificates(true);
        $deviceTypeEdgeGateway->setHasVpn(true);
        $deviceTypeEdgeGateway->setHasVariables(true);
        $deviceTypeEdgeGateway->setHasGsm(true);
        $deviceTypeEdgeGateway->setHasRequestConfig(true);
        $deviceTypeEdgeGateway->setFieldSerialNumber(FieldRequirement::REQUIRED);
        $deviceTypeEdgeGateway->setFieldModel(FieldRequirement::OPTIONAL);
        $deviceTypeEdgeGateway->setFieldRegistrationId(FieldRequirement::OPTIONAL);
        $deviceTypeEdgeGateway->setFieldEndorsementKey(FieldRequirement::OPTIONAL);
        $deviceTypeEdgeGateway->setFieldHardwareVersion(FieldRequirement::OPTIONAL);
        $deviceTypeEdgeGateway->setHasDeviceCommands(true);
        $deviceTypeEdgeGateway->setDeviceCommandMaxRetries(3);
        $deviceTypeEdgeGateway->setEnableConnectionAggregation(true);
        $this->addDeviceTypeCertificateType($manager, $deviceTypeEdgeGateway, $deviceVpnCertificateType);
        $manager->persist($deviceTypeEdgeGateway);

        $deviceType = new DeviceType();
        $deviceType->setName('Edge gateway with VPN Container Client');
        $deviceType->setDeviceName('Edge gateway');
        $deviceType->setCertificateCommonNamePrefix('egvcc');
        $deviceType->setIcon(DeviceTypeIcon::DEVICES);
        $deviceType->setColor('#034078');
        $deviceType->setHasFirmware1(true);
        $deviceType->setNameFirmware1('Firmware');
        $deviceType->setHasConfig1(true);
        $deviceType->setNameConfig1('Config');
        $deviceType->setFormatConfig1(ConfigFormat::JSON);
        $deviceType->setAuthenticationMethod(AuthenticationMethod::BASIC);
        $deviceType->setCredentialsSource(CredentialsSource::USER);
        $deviceType->setRoutePrefix('/api/edgegatewayvcc');
        $deviceType->setCommunicationProcedure(CommunicationProcedure::EDGEGATEWAY_WITH_VPNCONTAINERCLIENT);
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
        $deviceType->setEnableConnectionAggregation(true);
        $deviceType->setHasEndpointDevices(true);
        $deviceType->setHasMasquerade(true);
        $deviceType->setVirtualSubnetCidr(30);
        $this->addDeviceTypeCertificateType($manager, $deviceType, $deviceVpnCertificateType);
        $manager->persist($deviceType);

        $deviceType = new DeviceType();
        $deviceType->setName('Monitoring system device');
        $deviceType->setDeviceName('Monitoring system device');
        $deviceType->setCertificateCommonNamePrefix('msd');
        $deviceType->setIcon(DeviceTypeIcon::SEARCH);
        $deviceType->setColor('#474D40');
        $deviceType->setAuthenticationMethod(AuthenticationMethod::NONE);
        $deviceType->setCommunicationProcedure(CommunicationProcedure::NONE_VPN);
        $deviceType->setHasVariables(true);
        $deviceType->setHasCertificates(true);
        $deviceType->setHasVpn(true);
        $deviceType->setHasDeviceToNetworkConnection(true);
        $this->addDeviceTypeCertificateType($manager, $deviceType, $deviceVpnCertificateType);
        $manager->persist($deviceType);

        $deviceType = new DeviceType();
        $deviceType->setName('Standalone VPN device');
        $deviceType->setDeviceName('Standalone VPN device');
        $deviceType->setCertificateCommonNamePrefix('svd');
        $deviceType->setIcon(DeviceTypeIcon::DEVICES);
        $deviceType->setColor('#FF4900');
        $deviceType->setAuthenticationMethod(AuthenticationMethod::NONE);
        $deviceType->setCommunicationProcedure(CommunicationProcedure::NONE_VPN);
        $deviceType->setHasVariables(true);
        $deviceType->setHasCertificates(true);
        $deviceType->setHasVpn(true);
        $this->addDeviceTypeCertificateType($manager, $deviceType, $deviceVpnCertificateType);
        $manager->persist($deviceType);

        $manager->flush();

        $this->addReference(self::DEVICETYPE_ROUTERTK800_REFERENCE, $deviceTypeRouterTk800);
        $this->addReference(self::DEVICETYPE_EDGEGATEWAY_REFERENCE, $deviceTypeEdgeGateway);
    }

    protected function addDeviceTypeCertificateType(ObjectManager $manager, DeviceType $deviceType, CertificateType $certificateType, CertificateEncoding $certificateEncoding = CertificateEncoding::HEX)
    {
        $deviceTypeCertificateType = new DeviceTypeCertificateType();
        $deviceTypeCertificateType->setDeviceType($deviceType);
        $deviceTypeCertificateType->setCertificateType($certificateType);
        $deviceTypeCertificateType->setEnableCertificatesAutoRenew(true);
        $deviceTypeCertificateType->setCertificatesAutoRenewDaysBefore(14);
        $deviceTypeCertificateType->setCertificateEncoding($certificateEncoding);

        $deviceType->addCertificateType($deviceTypeCertificateType);
        $manager->persist($deviceTypeCertificateType);
    }

    public function getDependencies(): array
    {
        return [
            CertificateTypeFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['prod', 'deviceType:initialize', 'test'];
    }
}
