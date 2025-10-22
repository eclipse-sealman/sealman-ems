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
import { getFields, TextFilter } from "@arteneo/forge";
import MultiselectDeviceTypeFilter from "~app/components/Table/filters/MultiselectDeviceTypeFilter";
import DeviceFilter from "~app/components/Table/filters/DeviceFilter";

const filters = {
    deviceType: (
        <MultiselectDeviceTypeFilter
            {...{ endpoint: "/options/devicetonetwork/device/types", filterBy: "device.deviceType" }}
        />
    ),
    device: <DeviceFilter {...{ endpoint: "/options/devicetonetwork/devices" }} />,
    sourceDeviceToNetwork: <TextFilter />,
    destinationDeviceToNetwork: <TextFilter />,
};

export default getFields(filters);
