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
import { DateTimeFromFilter, DateTimeToFilter, getFields, SelectEnumFilter, TextFilter } from "@arteneo/forge";
import MultiselectDeviceTypeFilter from "~app/components/Table/filters/MultiselectDeviceTypeFilter";
import DeviceFilter from "~app/components/Table/filters/DeviceFilter";
import { commandStatus } from "~app/entities/DeviceCommand/enums";

const filters = {
    deviceType: (
        <MultiselectDeviceTypeFilter {...{ endpoint: "/options/device/types", filterBy: "device.deviceType" }} />
    ),
    device: <DeviceFilter {...{ endpoint: "/options/devices" }} />,
    commandStatus: <SelectEnumFilter {...{ enum: commandStatus }} />,
    commandName: <TextFilter />,
    updatedAtFrom: <DateTimeFromFilter {...{ filterBy: "updatedAt" }} />,
    updatedAtTo: <DateTimeToFilter {...{ filterBy: "updatedAt" }} />,
    commandTransactionId: <TextFilter />,
    createdAtFrom: <DateTimeFromFilter {...{ filterBy: "createdAt" }} />,
    createdAtTo: <DateTimeToFilter {...{ filterBy: "createdAt" }} />,
};

export default getFields(filters);
