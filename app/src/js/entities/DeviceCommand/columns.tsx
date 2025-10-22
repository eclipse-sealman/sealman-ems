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
import { getColumns, TextColumn } from "@arteneo/forge";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";
import VpnDeviceColumn from "~app/components/Table/columns/VpnDeviceColumn";
import TextSqueezedCopyColumn from "~app/components/Table/columns/TextSqueezedCopyColumn";
import DateTimeSecondsColumn from "~app/components/Table/columns/DateTimeSecondsColumn";
import UpdatedAtByColumn from "~app/components/Table/columns/UpdatedAtByColumn";
import DeviceCommandStatusColumn from "~app/components/Table/columns/DeviceCommandStatusColumn";

const columns = {
    device: <VpnDeviceColumn />,
    commandStatus: <DeviceCommandStatusColumn />,
    commandName: <TextColumn />,
    commandTransactionId: <TextSqueezedCopyColumn maxWidth={240} />,
    commandStatusErrorCategory: <TextColumn />,
    commandStatusErrorPid: <TextColumn />,
    commandStatusErrorMessage: <TextColumn />,
    expireAt: <DateTimeSecondsColumn />,
    createdAt: <CreatedAtByColumn />,
    updatedAt: <UpdatedAtByColumn />,
};

export default getColumns(columns);
