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
    TextFilter,
} from "@arteneo/forge";
import MultiselectDeviceTypeFilter from "~app/components/Table/filters/MultiselectDeviceTypeFilter";
import TemplateFilter from "~app/components/Table/filters/TemplateFilter";

const filters = {
    deviceType: <MultiselectDeviceTypeFilter {...{ endpoint: "/options/device/types" }} />,
    enabled: <BooleanFilter />,
    staging: <BooleanFilter />,
    hasCertificate: <BooleanFilter />,
    isCertificateExpired: <BooleanFilter />,
    vpnConnected: <BooleanFilter />,
    template: <TemplateFilter {...{ endpoint: "/options/templates" }} />,
    accessTags: <SelectApiFilter {...{ endpoint: "/options/access/tags" }} />,
    labels: <SelectApiFilter {...{ endpoint: "/options/labels" }} />,
    identifier: <TextFilter />,
    name: <TextFilter />,
    serialNumber: <TextFilter />,
    imsi: <TextFilter />,
    updatedBy: <SelectApiFilter {...{ endpoint: "/options/users" }} />,
    updatedAtFrom: <DateTimeFromFilter {...{ filterBy: "updatedAt" }} />,
    updatedAtTo: <DateTimeToFilter {...{ filterBy: "updatedAt" }} />,
    createdBy: <SelectApiFilter {...{ endpoint: "/options/users" }} />,
    createdAtFrom: <DateTimeFromFilter {...{ filterBy: "createdAt" }} />,
    createdAtTo: <DateTimeToFilter {...{ filterBy: "createdAt" }} />,
};

export default getFields(filters);
