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
import { getColumns, TextColumn } from "@arteneo/forge";
import DateTimeSecondsColumn from "~app/components/Table/columns/DateTimeSecondsColumn";
import LicenseContentShow from "~app/entities/OpenSourceLicense/actions/LicenseContentShow";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";

const columns = {
    name: <TextColumn />,
    version: <TextColumn />,
    licenseType: <TextColumn />,
    description: <TextColumn />,
    createdAt: <DateTimeSecondsColumn />,
    actions: (
        <BuilderActionsColumn
            {...{
                render: () => <LicenseContentShow />,
            }}
        />
    ),
};

export default getColumns(columns);
