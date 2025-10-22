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
import {
    getColumns,
    BooleanColumn,
    TextColumn,
    CollectionRepresentationColumn,
    RepresentationColumn,
} from "@arteneo/forge";
import DeviceTypeColumn from "~app/components/Table/columns/DeviceTypeColumn";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";
import TextSqueezedCopyColumn from "~app/components/Table/columns/TextSqueezedCopyColumn";
import CertificateColumn from "~app/components/Table/columns/CertificateColumn";
import DateTimeSecondsColumn from "~app/components/Table/columns/DateTimeSecondsColumn";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";
import UpdatedAtByColumn from "~app/components/Table/columns/UpdatedAtByColumn";
import CidrEnumColumn from "~app/components/Table/columns/CidrEnumColumn";
import UptimeColumn from "~app/components/Table/columns/UptimeColumn";
import VpnExpand from "~app/entities/Device/actions/VpnExpand";
import LogsExpand from "~app/entities/Device/actions/LogsExpand";
import FormatBytesColumn from "~app/components/Table/columns/FormatBytesColumn";
import CertificateExpiredColumn from "~app/components/Table/columns/CertificateExpiredColumn";
import CertificatesExpand from "~app/components/Table/actions/CertificatesExpand";
import CertificateTextColumn from "~app/components/Table/columns/CertificateTextColumn";
import CertificateBooleanColumn from "~app/components/Table/columns/CertificateBooleanColumn";
import CertificateDateTimeSecondsColumn from "~app/components/Table/columns/CertificateDateTimeSecondsColumn";

const columns = {
    deviceType: <DeviceTypeColumn />,
    enabled: <BooleanColumn />,
    staging: <BooleanColumn />,
    template: <RepresentationColumn />,
    accessTags: <CollectionRepresentationColumn disableSorting />,
    labels: <CollectionRepresentationColumn disableSorting />,
    identifier: <TextColumn />,
    name: <TextColumn />,
    description: <TextSqueezedCopyColumn maxWidth={100} />,
    uuid: <TextColumn />,
    serialNumber: <TextColumn />,
    registrationId: <TextColumn />,
    endorsementKey: <TextColumn />,
    hardwareVersion: <TextColumn />,
    commandRetryCount: <TextColumn />,
    model: <TextColumn />,
    reinstallFirmware1: <BooleanColumn />,
    reinstallFirmware2: <BooleanColumn />,
    reinstallFirmware3: <BooleanColumn />,
    reinstallConfig1: <BooleanColumn />,
    reinstallConfig2: <BooleanColumn />,
    reinstallConfig3: <BooleanColumn />,
    requestDiagnoseData: <BooleanColumn />,
    requestConfigData: <BooleanColumn />,
    connectionAmount: <TextColumn />,

    vpnConnected: <BooleanColumn />,
    vpnIp: <TextColumn />,
    virtualSubnet: <TextColumn />,
    virtualIp: <TextColumn />,
    virtualSubnetCidr: <CidrEnumColumn />,
    // Certificate columns below only use deviceVpn certificate category
    certificate: <CertificateColumn disableSorting />,
    certificateSubject: <CertificateTextColumn />,
    certificateCaSubject: <CertificateTextColumn />,
    hasCertificate: <CertificateBooleanColumn />,
    isCertificateExpired: <CertificateExpiredColumn />,
    certificateValidTo: <CertificateDateTimeSecondsColumn />,
    vpnTrafficIn: <FormatBytesColumn />,
    vpnTrafficOut: <FormatBytesColumn />,
    vpnLastConnectionAt: <DateTimeSecondsColumn />,

    imei: <TextColumn />,
    imsi: <TextColumn />,
    imsi2: <TextColumn />,
    operatorCode: <TextColumn />,
    band: <TextColumn />,
    cellId: <TextColumn />,
    networkGeneration: <TextColumn />,
    rsrp: <TextColumn />,
    cellularIp1: <TextColumn />,
    cellularUptime1: <TextColumn />,
    cellularUptimeSeconds1: <UptimeColumn uptimeSecondsPath={"cellularUptimeSeconds1"} />,
    cellularIp2: <TextColumn />,
    cellularUptime2: <TextColumn />,
    cellularUptimeSeconds2: <UptimeColumn uptimeSecondsPath={"cellularUptimeSeconds2"} />,
    firmwareVersion1: <TextColumn />,
    firmwareVersion2: <TextColumn />,
    firmwareVersion3: <TextColumn />,
    xForwardedFor: <TextColumn />,
    host: <TextColumn />,
    ipv6Prefix: <TextColumn />,
    uptime: <TextColumn />,
    uptimeSeconds: <UptimeColumn uptimeSecondsPath={"uptimeSeconds"} />,
    seenAt: <DateTimeSecondsColumn />,

    createdAt: <CreatedAtByColumn />,
    updatedAt: <UpdatedAtByColumn />,
    actions: (
        <BuilderActionsColumn
            {...{
                render: ({ detailsAction, editAction, deleteAction }) => (
                    <>
                        {detailsAction}
                        <CertificatesExpand entityPrefix="device" />
                        <VpnExpand />
                        <LogsExpand />
                        {editAction}
                        {deleteAction}
                    </>
                ),
            }}
        />
    ),
};

export default getColumns(columns);
