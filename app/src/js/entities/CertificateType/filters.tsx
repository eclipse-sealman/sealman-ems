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
import { certificateCategory, certificateEntity } from "~app/entities/CertificateType/enums";

const filters = {
    name: <TextFilter />,
    commonNamePrefix: <TextFilter />,
    variablePrefix: <TextFilter />,
    certificateCategory: <SelectEnumFilter {...{ enum: certificateCategory }} />,
    certificateEntity: <SelectEnumFilter {...{ enum: certificateEntity }} />,
    createdBy: <SelectApiFilter {...{ endpoint: "/options/users" }} />,
    createdAtFrom: <DateTimeFromFilter {...{ filterBy: "createdAt" }} />,
    createdAtTo: <DateTimeToFilter {...{ filterBy: "createdAt" }} />,
    updatedBy: <SelectApiFilter {...{ endpoint: "/options/users" }} />,
    updatedAtFrom: <DateTimeFromFilter {...{ filterBy: "updatedAt" }} />,
    updatedAtTo: <DateTimeToFilter {...{ filterBy: "updatedAt" }} />,
};

export default getFields(filters);
