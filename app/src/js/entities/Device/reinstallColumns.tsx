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
import { BooleanColumn, getColumns, RepresentationColumn, TextColumn } from "@arteneo/forge";
import DateTimeSecondsColumn from "~app/components/Table/columns/DateTimeSecondsColumn";
import UpdatedAtByColumn from "~app/components/Table/columns/UpdatedAtByColumn";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";

const columns = {
    name: <TextColumn />,
    // TODO Maybye serialNumber or IMSI depending on configuration? - Arek please ask customer
    serialNumber: <TextColumn />,
    template: <RepresentationColumn />,
    staging: <BooleanColumn />,
    enabled: <BooleanColumn />,
    seenAt: <DateTimeSecondsColumn />,
    updatedAt: <UpdatedAtByColumn />,
    createdAt: <CreatedAtByColumn />,
};

export default getColumns(columns);
