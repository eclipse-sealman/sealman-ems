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

import React from "react";
import { EnumColumn, CollectionRepresentationColumn, TextColumn } from "@arteneo/forge";
import { getRows } from "~app/utilities/common";
import { DisplayRowsInterface } from "~app/components/Display/Display";
import { DeviceConfigurationTypeInterface } from "~app/entities/DeviceType/definitions";
import { cidr } from "~app/enums/Cidr";
import { DeviceInterface } from "~app/entities/Device/definitions";
import { DisplayRowTitleProps } from "~app/components/Display/DisplayRowTitle";
import CreatedAtByLineColumn from "~app/components/Table/columns/CreatedAtByLineColumn";
import UpdatedAtByLineColumn from "~app/components/Table/columns/UpdatedAtByLineColumn";
import MasqueradeTypeColumn from "~app/components/Table/columns/MasqueradeTypeColumn";
import BooleanXsColumn from "~app/components/Table/columns/BooleanXsColumn";
import DateTimeSecondsColumn from "~app/components/Table/columns/DateTimeSecondsColumn";
import CertificateXsColumn from "~app/components/Table/columns/CertificateXsColumn";
import FormatBytesColumn from "~app/components/Table/columns/FormatBytesColumn";
import UptimeColumn from "~app/components/Table/columns/UptimeColumn";
import ConnectionAmountColumn from "~app/components/Table/columns/ConnectionAmountColumn";
import TemplateDetailsColumn from "~app/components/Table/columns/TemplateDetailsColumn";
import DeviceTypeColumn from "~app/components/Table/columns/DeviceTypeColumn";
import { isFieldHidden } from "~app/enums/FieldRequirement";

interface SpecificCommunicationProcedureRowsInterface {
    [key: string]: string[];
}

const specificCommunicationProcedureRows: SpecificCommunicationProcedureRowsInterface = {
    edgeGateway: [
        "deviceType",
        "name",
        "serialNumber",
        "description",
        "labels",
        "enabled",
        "staging",
        "template",
        "firmwareVersion1",
        "firmwareVersion2",
        "firmwareVersion3",
        "hardwareVersion",
        "reinstallConfig1",
        "reinstallConfig2",
        "reinstallConfig3",
        "reinstallFirmware1",
        "reinstallFirmware2",
        "reinstallFirmware3",
        "requestConfigData",
        "seenAt",
        "connectionAmount",
        "accessTags",
        "xForwardedFor",
        "host",
        "updatedAtBy",
        "createdAtBy",
    ],
    edgeGatewayWithVpnContainerClient: [
        "deviceType",
        "name",
        "serialNumber",
        "description",
        "labels",
        "enabled",
        "staging",
        "template",
        "firmwareVersion1",
        "firmwareVersion2",
        "firmwareVersion3",
        "hardwareVersion",
        "reinstallConfig1",
        "reinstallConfig2",
        "reinstallConfig3",
        "reinstallFirmware1",
        "reinstallFirmware2",
        "reinstallFirmware3",
        "requestConfigData",
        "vpnConnected",
        "virtualSubnet",
        "vpnTrafficIn",
        "vpnTrafficOut",
        "certificate",
        "vpnIp",
        "vpnLastConnectionAt",
        "seenAt",
        "connectionAmount",
        "accessTags",
        "xForwardedFor",
        "host",
        "updatedAtBy",
        "createdAtBy",
    ],
    vpnContainerClient: [
        "deviceType",
        "name",
        "uuid",
        "description",
        "labels",
        "enabled",
        "staging",
        "template",
        "vpnConnected",
        "virtualSubnet",
        "vpnTrafficIn",
        "vpnTrafficOut",
        "certificate",
        "vpnIp",
        "vpnLastConnectionAt",
        "accessTags",
        "seenAt",
        "connectionAmount",
        "masqueradeType",
        "virtualSubnetCidr",
        "xForwardedFor",
        "host",
        "updatedAtBy",
        "createdAtBy",
    ],

    routerOneConfig: [
        "deviceType",
        "name",
        "serialNumber",
        "description",
        "labels",
        "enabled",
        "staging",
        "template",
        "firmwareVersion1",
        "firmwareVersion2",
        "firmwareVersion3",
        "model",
        "imei",
        "imsi",
        "reinstallConfig1",
        "reinstallConfig2",
        "reinstallConfig3",
        "reinstallFirmware1",
        "reinstallFirmware2",
        "reinstallFirmware3",
        "requestDiagnoseData",
        "vpnConnected",
        "virtualSubnet",
        "vpnTrafficIn",
        "vpnTrafficOut",
        "certificate",
        "vpnIp",
        "vpnLastConnectionAt",
        "networkGeneration",
        "rsrp",
        "operatorCode",
        "band",
        "cellId",
        "cellularIp1",
        "cellularUptime1",
        "uptime",
        "seenAt",
        "accessTags",
        "virtualSubnetCidr",
        "xForwardedFor",
        "host",
        "updatedAtBy",
        "createdAtBy",
    ],
    router: [
        "deviceType",
        "name",
        "serialNumber",
        "description",
        "labels",
        "enabled",
        "staging",
        "template",
        "firmwareVersion1",
        "firmwareVersion2",
        "firmwareVersion3",
        "model",
        "imei",
        "imsi",
        "reinstallConfig1",
        "reinstallConfig2",
        "reinstallConfig3",
        "reinstallFirmware1",
        "reinstallFirmware2",
        "reinstallFirmware3",
        "requestDiagnoseData",
        "vpnConnected",
        "virtualSubnet",
        "vpnTrafficIn",
        "vpnTrafficOut",
        "certificate",
        "vpnIp",
        "vpnLastConnectionAt",
        "networkGeneration",
        "rsrp",
        "operatorCode",
        "band",
        "cellId",
        "cellularIp1",
        "cellularUptime1",
        "cellularIp2",
        "cellularUptime2",
        "ipv6Prefix",
        "uptime",
        "seenAt",
        "accessTags",
        "virtualSubnetCidr",
        "xForwardedFor",
        "host",
        "updatedAtBy",
        "createdAtBy",
    ],
    routerDsa: [
        "deviceType",
        "name",
        "serialNumber",
        "description",
        "labels",
        "enabled",
        "staging",
        "template",
        "firmwareVersion1",
        "firmwareVersion2",
        "firmwareVersion3",
        "model",
        "imei",
        "imsi",
        "reinstallConfig1",
        "reinstallConfig2",
        "reinstallConfig3",
        "reinstallFirmware1",
        "reinstallFirmware2",
        "reinstallFirmware3",
        "requestDiagnoseData",
        "vpnConnected",
        "virtualSubnet",
        "vpnTrafficIn",
        "vpnTrafficOut",
        "certificate",
        "vpnIp",
        "vpnLastConnectionAt",
        "networkGeneration",
        "rsrp",
        "operatorCode",
        "band",
        "cellId",
        "cellularIp1",
        "cellularUptime1",
        "cellularIp2",
        "cellularUptime2",
        "ipv6Prefix",
        "uptime",
        "seenAt",
        "accessTags",
        "virtualSubnetCidr",
        "xForwardedFor",
        "host",
        "updatedAtBy",
        "createdAtBy",
    ],
    noneVpn: [
        "deviceType",
        "name",
        "description",
        "labels",
        "enabled",
        "vpnConnected",
        "vpnIp",
        "vpnTrafficIn",
        "vpnTrafficOut",
        "certificate",
        "vpnLastConnectionAt",
        "accessTags",
        "updatedAtBy",
        "createdAtBy",
    ],
};

const composeGetTitleProps = (device: DeviceInterface) => {
    return (rowKey: string): DisplayRowTitleProps => {
        switch (rowKey) {
            case "reinstallConfig1":
                return {
                    title: "label.reinstallConfig",
                    titleVariables: {
                        config: device.deviceType.nameConfig1,
                    },
                };
            case "reinstallConfig2":
                return {
                    title: "label.reinstallConfig",
                    titleVariables: {
                        config: device.deviceType.nameConfig2,
                    },
                };
            case "reinstallConfig3":
                return {
                    title: "label.reinstallConfig",
                    titleVariables: {
                        config: device.deviceType.nameConfig3,
                    },
                };
            case "reinstallFirmware1":
                return {
                    title: "label.reinstallFirmware",
                    titleVariables: {
                        firmware: device.deviceType.nameFirmware1,
                    },
                };
            case "reinstallFirmware2":
                return {
                    title: "label.reinstallFirmware",
                    titleVariables: {
                        firmware: device.deviceType.nameFirmware2,
                    },
                };
            case "reinstallFirmware3":
                return {
                    title: "label.reinstallFirmware",
                    titleVariables: {
                        firmware: device.deviceType.nameFirmware3,
                    },
                };
            case "firmwareVersion1":
                return {
                    title: "label.firmwareVersion",
                    titleVariables: {
                        firmware: device.deviceType.nameFirmware1,
                    },
                };
            case "firmwareVersion2":
                return {
                    title: "label.firmwareVersion",
                    titleVariables: {
                        firmware: device.deviceType.nameFirmware2,
                    },
                };
            case "firmwareVersion3":
                return {
                    title: "label.firmwareVersion",
                    titleVariables: {
                        firmware: device.deviceType.nameFirmware3,
                    },
                };
        }

        return {
            title: "label." + rowKey,
        };
    };
};

const composeGetRows = (deviceType: DeviceConfigurationTypeInterface) => {
    const rows: DisplayRowsInterface = {
        deviceType: <DeviceTypeColumn />,
        name: <TextColumn />,
        description: <TextColumn />,
        labels: <CollectionRepresentationColumn />,
        enabled: <BooleanXsColumn />,
    };

    if (deviceType.hasTemplates) {
        rows.template = <TemplateDetailsColumn />;
        rows.staging = <BooleanXsColumn />;
    }

    rows.identifier = <TextColumn />;
    rows.uuid = <TextColumn />;

    if (!isFieldHidden(deviceType.fieldSerialNumber)) {
        rows.serialNumber = <TextColumn />;
    }

    if (!isFieldHidden(deviceType.fieldModel)) {
        rows.model = <TextColumn />;
    }

    if (!isFieldHidden(deviceType.fieldRegistrationId)) {
        rows.registrationId = <TextColumn />;
    }

    if (!isFieldHidden(deviceType.fieldEndorsementKey)) {
        rows.endorsementKey = <TextColumn />;
    }

    if (!isFieldHidden(deviceType.fieldHardwareVersion)) {
        rows.hardwareVersion = <TextColumn />;
    }

    if (deviceType.enableConnectionAggregation) {
        rows.connectionAmount = (
            <ConnectionAmountColumn
                {...{
                    connectionAmountFromPath: "connectionAmountFrom",
                    connectionAggregationPeriod: deviceType.connectionAggregationPeriod,
                }}
            />
        );
    }

    if (deviceType.hasConfig1) {
        rows.reinstallConfig1 = <BooleanXsColumn />;
    }

    if (deviceType.hasConfig2) {
        rows.reinstallConfig2 = <BooleanXsColumn />;
    }

    if (deviceType.hasConfig3) {
        rows.reinstallConfig3 = <BooleanXsColumn />;
    }

    if (deviceType.hasFirmware1) {
        rows.reinstallFirmware1 = <BooleanXsColumn />;
        rows.firmwareVersion1 = <TextColumn />;
    }

    if (deviceType.hasFirmware2) {
        rows.reinstallFirmware2 = <BooleanXsColumn />;
        rows.firmwareVersion2 = <TextColumn />;
    }

    if (deviceType.hasFirmware3) {
        rows.reinstallFirmware3 = <BooleanXsColumn />;
        rows.firmwareVersion3 = <TextColumn />;
    }

    if (deviceType.hasRequestDiagnose) {
        rows.requestDiagnoseData = <BooleanXsColumn />;
    }

    if (deviceType.hasRequestConfig) {
        rows.requestConfigData = <BooleanXsColumn />;
    }

    if (deviceType.isEndpointDevicesAvailable) {
        rows.virtualSubnetCidr = <EnumColumn {...{ enum: cidr }} />;
        rows.virtualSubnet = <TextColumn />;
    }

    if (deviceType.isVpnAvailable) {
        rows.vpnIp = <TextColumn />;
        rows.vpnConnected = <BooleanXsColumn />;
        rows.vpnTrafficIn = <FormatBytesColumn />;
        rows.vpnTrafficOut = <FormatBytesColumn />;
        rows.vpnLastConnectionAt = <DateTimeSecondsColumn />;
    }

    if (deviceType.hasCertificates) {
        rows.certificate = <CertificateXsColumn />;
    }

    if (deviceType.isMasqueradeAvailable) {
        rows.masqueradeType = <MasqueradeTypeColumn />;
    }

    if (deviceType.hasGsm) {
        rows.imei = <TextColumn />;
        if (!isFieldHidden(deviceType.fieldImsi)) {
            rows.imsi = <TextColumn />;
        }
        rows.imsi2 = <TextColumn />;
        rows.operatorCode = <TextColumn />;
        rows.band = <TextColumn />;
        rows.cellId = <TextColumn />;
        rows.networkGeneration = <TextColumn />;
        // TODO check whether to use rsrp or rsrpValue  - Arek please ask customer
        rows.rsrp = <TextColumn />;
        rows.cellularIp1 = <TextColumn />;
        rows.cellularUptime1 = <UptimeColumn {...{ uptimeSecondsPath: "cellularUptimeSeconds1" }} />;
        rows.cellularIp2 = <TextColumn />;
        rows.cellularUptime2 = <UptimeColumn {...{ uptimeSecondsPath: "cellularUptimeSeconds2" }} />;
    }

    rows.xForwardedFor = <TextColumn />;
    rows.host = <TextColumn />;
    rows.ipv6Prefix = <TextColumn />;
    rows.uptime = <UptimeColumn {...{ uptimeSecondsPath: "uptimeSeconds" }} />;
    rows.seenAt = <DateTimeSecondsColumn />;

    rows.accessTags = <CollectionRepresentationColumn />;
    rows.updatedAtBy = <UpdatedAtByLineColumn />;
    rows.createdAtBy = <CreatedAtByLineColumn />;

    return getRows(prepareCommunicationProcedureRows(rows, deviceType));
};

const prepareCommunicationProcedureRows = (
    rows: DisplayRowsInterface,
    deviceType: DeviceConfigurationTypeInterface
): DisplayRowsInterface => {
    if (typeof deviceType?.communicationProcedure === "undefined") {
        return rows;
    }

    if (typeof specificCommunicationProcedureRows[deviceType.communicationProcedure] === "undefined") {
        return rows;
    }

    const requiredRows = specificCommunicationProcedureRows[deviceType.communicationProcedure];

    const preparedRows: DisplayRowsInterface = {};

    requiredRows.forEach((rowName) => {
        if (typeof rows[rowName] !== "undefined") {
            preparedRows[rowName] = rows[rowName];
        }
    });

    return preparedRows;
};

export default composeGetRows;
export { composeGetTitleProps };
