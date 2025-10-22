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
import { BooleanColumn, getColumns, TextColumn } from "@arteneo/forge";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";
import DeviceTypesListColumn from "~app/components/Table/columns/DeviceTypesListColumn";

const columns = {
    username: <TextColumn />,
    password: <TextColumn />,
    userDeviceTypes: <DeviceTypesListColumn disableSorting path="deviceTypes" />,
    enabled: <BooleanColumn />,
    actions: <BuilderActionsColumn />,
};

export default getColumns(columns);
