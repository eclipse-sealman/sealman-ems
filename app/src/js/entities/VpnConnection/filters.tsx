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
import { getFields, SelectApiFilter } from "@arteneo/forge";
import MultiselectDeviceTypeFilter from "~app/components/Table/filters/MultiselectDeviceTypeFilter";
import DeviceFilter from "~app/components/Table/filters/DeviceFilter";
import EndpointDeviceFilter from "~app/components/Table/filters/EndpointDeviceFilter";

const filters = {
    deviceType: (
        <MultiselectDeviceTypeFilter {...{ endpoint: "/options/vpn/device/types", filterBy: "device.deviceType" }} />
    ),
    device: <DeviceFilter {...{ endpoint: "/options/vpn/devices" }} />,
    endpointDevice: <EndpointDeviceFilter {...{ endpoint: "/options/vpn/deviceendpointdevices" }} />,
    user: <SelectApiFilter {...{ endpoint: "/options/users" }} />,
};

export default getFields(filters);
