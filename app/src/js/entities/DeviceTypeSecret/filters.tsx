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
    BooleanFilter,
    DateTimeFromFilter,
    DateTimeToFilter,
    getFields,
    SelectApiFilter,
    SelectEnumFilter,
    TextFilter,
} from "@arteneo/forge";
import { secretValueBehaviour } from "~app/entities/DeviceTypeSecret/enums";

const filters = {
    name: <TextFilter />,
    variableNamePrefix: <TextFilter />,
    description: <TextFilter />,
    useAsVariable: <BooleanFilter />,
    secretValueBehaviour: <SelectEnumFilter enum={secretValueBehaviour} />,
    manualEdit: <BooleanFilter />,
    manualForceRenewal: <BooleanFilter />,
    accessTags: <SelectApiFilter {...{ endpoint: "/options/access/tags" }} />,
    updatedBy: <SelectApiFilter {...{ endpoint: "/options/users" }} />,
    updatedAtFrom: <DateTimeFromFilter {...{ filterBy: "updatedAt" }} />,
    updatedAtTo: <DateTimeToFilter {...{ filterBy: "updatedAt" }} />,
    createdBy: <SelectApiFilter {...{ endpoint: "/options/users" }} />,
    createdAtFrom: <DateTimeFromFilter {...{ filterBy: "createdAt" }} />,
    createdAtTo: <DateTimeToFilter {...{ filterBy: "createdAt" }} />,
};

export default getFields(filters);
