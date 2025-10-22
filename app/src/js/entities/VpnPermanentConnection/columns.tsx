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
import VpnTargetColumn from "~app/components/Table/columns/VpnTargetColumn";
import VpnConnectionColumn from "~app/components/Table/columns/VpnConnectionColumn";

const columns = {
    target: <VpnTargetColumn />,
    connection: <VpnConnectionColumn />,
    sourceDeviceToNetwork: <TextColumn path={"source"} />,
    destinationDeviceToNetwork: <TextColumn path={"destination"} />,
};

export default getColumns(columns);
