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
import { ActionsColumn, getColumns, TextColumn } from "@arteneo/forge";
import LogLevelColumn from "~app/components/Table/columns/LogLevelColumn";
import TextSqueezedCopyColumn from "~app/components/Table/columns/TextSqueezedCopyColumn";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";
import ResultDialogMessage from "~app/components/Table/actions/ResultDialogMessage";
import VpnDeviceColumn from "~app/components/Table/columns/VpnDeviceColumn";
import DateTimeSecondsColumn from "~app/components/Table/columns/DateTimeSecondsColumn";
import ResultDialogContent from "~app/components/Table/actions/ResultDialogContent";

const columns = {
    logLevel: <LogLevelColumn />,
    device: <VpnDeviceColumn disableSorting={true} />,
    message: <TextSqueezedCopyColumn maxWidth={300} />,
    serialNumber: <TextColumn />,
    registrationId: <TextColumn />,
    endorsementKey: <TextColumn />,
    hardwareVersion: <TextColumn />,
    model: <TextColumn />,
    firmwareVersion1: <TextColumn />,
    firmwareVersion2: <TextColumn />,
    firmwareVersion3: <TextColumn />,
    imei: <TextColumn />,
    imsi: <TextColumn />,
    imsi2: <TextColumn />,
    operatorCode: <TextColumn />,
    band: <TextColumn />,
    cellId: <TextColumn />,
    networkGeneration: <TextColumn />,
    rsrp: <TextColumn />,
    rsrpValue: <TextColumn />,
    cellularIp1: <TextColumn />,
    cellularUptime1: <TextColumn />,
    cellularUptimeSeconds1: <TextColumn />,
    cellularIp2: <TextColumn />,
    cellularUptime2: <TextColumn />,
    cellularUptimeSeconds2: <TextColumn />,
    xForwardedFor: <TextColumn />,
    host: <TextColumn />,
    ipv6Prefix: <TextColumn />,
    uptime: <TextColumn />,
    uptimeSeconds: <TextColumn />,
    seenAt: <DateTimeSecondsColumn />,
    createdAt: <CreatedAtByColumn />,
    actions: (
        <ActionsColumn>
            <ResultDialogMessage />
            <ResultDialogContent
                {...{
                    denyKey: "showContent",
                    denyBehavior: "hide",
                    dialogProps: (result) => ({
                        initializeEndpoint: "/communicationlog/content/" + result.id,
                        content: (payload) => payload?.content,
                    }),
                }}
            />
        </ActionsColumn>
    ),
};

export default getColumns(columns);
