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
import { getColumns, RepresentationColumn, TextColumn } from "@arteneo/forge";
import FirmwareNameColumn from "~app/entities/Firmware/columns/FirmwareNameColumn";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";
import UpdatedAtByColumn from "~app/components/Table/columns/UpdatedAtByColumn";
import TextSqueezedCopyColumn from "~app/components/Table/columns/TextSqueezedCopyColumn";
import DeviceTypeColumn from "~app/components/Table/columns/DeviceTypeColumn";

const columns = {
    deviceType: <DeviceTypeColumn />,
    name: <FirmwareNameColumn />,
    version: <TextColumn />,
    requiredFirmware: <RepresentationColumn />,
    uuid: <TextSqueezedCopyColumn />,
    createdAt: <CreatedAtByColumn />,
    updatedAt: <UpdatedAtByColumn />,
};

export default getColumns(columns);
