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
import { DateTimeFromFilter, DateTimeToFilter, getFields, SelectApiFilter, SelectEnumFilter } from "@arteneo/forge";
import { status, type } from "~app/entities/Maintenance/enums";

const filters = {
    status: <SelectEnumFilter {...{ enum: status }} />,
    type: <SelectEnumFilter {...{ enum: type }} />,
    maintenanceSchedule: <SelectApiFilter {...{ endpoint: "/options/maintenance/schedules" }} />,
    updatedAtFrom: <DateTimeFromFilter {...{ filterBy: "updatedAt" }} />,
    updatedAtTo: <DateTimeToFilter {...{ filterBy: "updatedAt" }} />,
    createdAtFrom: <DateTimeFromFilter {...{ filterBy: "createdAt" }} />,
    createdAtTo: <DateTimeToFilter {...{ filterBy: "createdAt" }} />,
};

export default getFields(filters);
