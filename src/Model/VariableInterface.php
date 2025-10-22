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

namespace App\Model;

interface VariableInterface
{
    public const VARIABLE_NAME_CERTIFICATE = 'certificate';
    public const VARIABLE_NAME_PRIVATE_KEY = 'privateKey';
    public const VARIABLE_NAME_CA = 'ca';
    public const VARIABLE_NAME_ROOT_CA = 'rootCa';
    public const VARIABLE_NAME_CERTIFICATE_PLAIN = 'certificatePlain';
    public const VARIABLE_NAME_PRIVATE_KEY_PLAIN = 'privateKeyPlain';
    public const VARIABLE_NAME_CA_PLAIN = 'caPlain';
    public const VARIABLE_NAME_ROOT_CA_PLAIN = 'rootCaPlain';
    public const VARIABLE_NAME_CERTIFICATE_CHECKSUM = 'certificateChecksum';
    public const VARIABLE_NAME_PRIVATE_KEY_CHECKSUM = 'privateKeyChecksum';
    public const VARIABLE_NAME_CA_CHECKSUM = 'caChecksum';
    public const VARIABLE_NAME_ROOT_CA_CHECKSUM = 'rootCaChecksum';
    public const VARIABLE_NAME_PRIVATE_KEY_PASSWORD = 'privateKeyPassword';

    public const VARIABLE_NAME_SERIAL = 'SerialNr';
    public const VARIABLE_NAME_SERIALNUMBER = 'serialNumber';
    public const VARIABLE_NAME_IDENTIFIER = 'identifier';
    public const VARIABLE_NAME_NAME = 'name';
    public const VARIABLE_NAME_SOURCEIP = 'SourceIP';
    public const VARIABLE_NAME_XFORWARDEDFORIP = 'XForwardedForIP';

    public const VARIABLE_NAME_VIRTUAL_SUBNET_IP = 'virtualSubnetIp';
    public const VARIABLE_NAME_VIRTUAL_SUBNET_CIDR = 'virtualSubnetCidr';
    public const VARIABLE_NAME_VIRTUAL_SUBNET = 'virtualSubnet';
    public const VARIABLE_NAME_VIRTUAL_IP = 'virtualIp';
    public const VARIABLE_NAME_VPN_IP = 'vpnIp';
    public const VARIABLE_NAME_VIP_SUBNET = 'vip_subnet';
    public const VARIABLE_NAME_VIP_PREFIX = 'vip_';
    public const VARIABLE_NAME_PIP_PREFIX = 'pip_';
    public const VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_PREFIX = 'virtualIp_';
    public const VARIABLE_NAME_ENDPOINT_DEVICE_PHYSICAL_IP_PREFIX = 'physicalIp_';
    public const VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_ARRAY = 'virtualIpArray';
    public const VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_HOST_PART_ARRAY = 'virtualIpHostPartArray';
    public const VARIABLE_NAME_ENDPOINT_DEVICE_PHYSICAL_IP_ARRAY = 'physicalIpArray';

    public const VARIABLE_NAME_IMEI = 'imei';
    public const VARIABLE_NAME_IMSI = 'imsi';
    public const VARIABLE_NAME_IMSI_UPPERCASE = 'IMSI';
    public const VARIABLE_NAME_IMSI2 = 'imsi2';
    public const VARIABLE_NAME_OPERATORCODE = 'operatorCode';
    public const VARIABLE_NAME_BAND = 'band';
    public const VARIABLE_NAME_CELLID = 'cellId';
    public const VARIABLE_NAME_NETWORKGENERATION = 'networkGeneration';
    public const VARIABLE_NAME_RSRP = 'rsrp';
    public const VARIABLE_NAME_RSRPVALUE = 'rsrpValue';
    public const VARIABLE_NAME_CELLULARIP1 = 'cellularIp1';
    public const VARIABLE_NAME_CELLULARUPTIME1 = 'cellularUptime1';
    public const VARIABLE_NAME_CELLULARUPTIMESECONDS1 = 'cellularUptimeSeconds1';
    public const VARIABLE_NAME_CELLULARIP2 = 'cellularIp2';
    public const VARIABLE_NAME_CELLULARUPTIME2 = 'cellularUptime2';
    public const VARIABLE_NAME_CELLULARUPTIMESECONDS2 = 'cellularUptimeSeconds2';

    public const VARIABLE_NAME_ENCODEDVPNCONFIG = 'encodedVpnConfig';

    public const VARIABLE_NAME_REGISTRATIONID = 'registrationId';
    public const VARIABLE_NAME_ENDORSEMENTKEY = 'endorsementKey';
    public const VARIABLE_NAME_HARDWAREVERSION = 'hardwareVersion';
    public const VARIABLE_NAME_FIRMWAREVERSION = 'firmwareVersion';
}
