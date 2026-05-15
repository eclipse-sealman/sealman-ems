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
import { sourceType } from "~app/entities/Firmware/enums";

const composeGetFilters = (deviceTypeId: number) => {
    const filters = {
        hardware: <SelectApiFilter {...{ endpoint: "/options/hardwares/" + deviceTypeId }} />,
        sourceType: <SelectEnumFilter {...{ enum: sourceType }} />,
        filename: <TextFilter />,
        md5: <TextFilter />,
        uuid: <TextFilter />,
        updatedBy: <SelectApiFilter {...{ endpoint: "/options/users" }} />,
        updatedAtFrom: <DateTimeFromFilter {...{ filterBy: "updatedAt" }} />,
        updatedAtTo: <DateTimeToFilter {...{ filterBy: "updatedAt" }} />,
        createdBy: <SelectApiFilter {...{ endpoint: "/options/users" }} />,
        createdAtFrom: <DateTimeFromFilter {...{ filterBy: "createdAt" }} />,
        createdAtTo: <DateTimeToFilter {...{ filterBy: "createdAt" }} />,
    };

    return getFields(filters);
};

export default composeGetFilters;
