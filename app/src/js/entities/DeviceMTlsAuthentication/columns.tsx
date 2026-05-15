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
import { EnumColumn, getColumns, TextColumn } from "@arteneo/forge";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";
import UpdatedAtByColumn from "~app/components/Table/columns/UpdatedAtByColumn";
import DeviceTypesListColumn from "~app/components/Table/columns/DeviceTypesListColumn";
import { crlType } from "~app/entities/DeviceMTlsAuthentication/enums";
import DateTimeSecondsColumn from "~app/components/Table/columns/DateTimeSecondsColumn";

const columns = {
    name: <TextColumn />,
    certificateCaSubject: <TextColumn />,
    certificateCaValidTo: <DateTimeSecondsColumn />,
    crlType: <EnumColumn {...{ enum: crlType }} />,
    deviceTypes: <DeviceTypesListColumn disableSorting path="deviceTypes" />,
    createdAt: <CreatedAtByColumn />,
    updatedAt: <UpdatedAtByColumn />,
    actions: <BuilderActionsColumn />,
};

export default getColumns(columns);
