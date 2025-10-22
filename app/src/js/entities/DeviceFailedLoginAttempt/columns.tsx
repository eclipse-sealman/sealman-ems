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
import DateTimeSecondsColumn from "~app/components/Table/columns/DateTimeSecondsColumn";
import DeviceTypeColumn from "~app/components/Table/columns/DeviceTypeColumn";
import { authenticationMethod } from "~app/entities/DeviceType/enums";

const columns = {
    deviceType: <DeviceTypeColumn />,
    userIdentifier: <TextColumn />,
    url: <TextColumn />,
    authenticationMethod: <EnumColumn {...{ enum: authenticationMethod }} />,
    remoteHost: <TextColumn />,
    createdAt: <DateTimeSecondsColumn />,
};

export default getColumns(columns);
