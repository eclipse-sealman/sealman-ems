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
    DateTimeFromFilter,
    DateTimeToFilter,
    getFields,
    SelectApiFilter,
    SelectEnumFilter,
    TextFilter,
} from "@arteneo/forge";
import MultiselectDeviceTypeFilter from "~app/components/Table/filters/MultiselectDeviceTypeFilter";
import DeviceFilter from "~app/components/Table/filters/DeviceFilter";
import { secretOperation } from "~app/entities/SecretLog/enums";

const filters = {
    deviceType: <MultiselectDeviceTypeFilter {...{ endpoint: "/options/vpn/device/types" }} />,
    device: <DeviceFilter {...{ endpoint: "/options/vpn/devices" }} />,
    message: <TextFilter />,
    operation: <SelectEnumFilter {...{ enum: secretOperation }} />,
    createdBy: <SelectApiFilter {...{ endpoint: "/options/users" }} />,
    createdAtFrom: <DateTimeFromFilter {...{ filterBy: "createdAt" }} />,
    createdAtTo: <DateTimeToFilter {...{ filterBy: "createdAt" }} />,
};

export default getFields(filters);
