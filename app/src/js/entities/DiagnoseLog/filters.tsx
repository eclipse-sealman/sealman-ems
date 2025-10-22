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
import { DateTimeFromFilter, DateTimeToFilter, getFields, SelectEnumFilter } from "@arteneo/forge";
import { logLevel } from "~app/enums/LogLevel";
import MultiselectDeviceTypeFilter from "~app/components/Table/filters/MultiselectDeviceTypeFilter";
import DeviceFilter from "~app/components/Table/filters/DeviceFilter";

const filters = {
    logLevel: <SelectEnumFilter {...{ enum: logLevel }} />,
    deviceType: <MultiselectDeviceTypeFilter {...{ endpoint: "/options/device/types" }} />,
    device: <DeviceFilter {...{ endpoint: "/options/devices" }} />,
    createdAtFrom: <DateTimeFromFilter {...{ filterBy: "createdAt" }} />,
    createdAtTo: <DateTimeToFilter {...{ filterBy: "createdAt" }} />,
};

export default getFields(filters);
