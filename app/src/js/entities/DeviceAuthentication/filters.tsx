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
import { BooleanFilter, getFields, TextFilter } from "@arteneo/forge";
import MultiselectDeviceTypeFilter from "~app/components/Table/filters/MultiselectDeviceTypeFilter";

const filters = {
    username: <TextFilter />,
    deviceType: (
        <MultiselectDeviceTypeFilter
            {...{ endpoint: "/options/device/types", label: "userDeviceType", filterBy: "userDeviceTypes.deviceType" }}
        />
    ),
    enabled: <BooleanFilter />,
};

export default getFields(filters);
