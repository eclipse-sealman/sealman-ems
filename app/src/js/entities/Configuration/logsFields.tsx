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
import { getFields, Text } from "@arteneo/forge";

const fields = {
    communicationLogsCleanupDuration: <Text {...{ required: true, help: true }} />,
    communicationLogsCleanupSize: <Text {...{ required: true, help: true }} />,
    diagnoseLogsCleanupDuration: <Text {...{ required: true, help: true }} />,
    diagnoseLogsCleanupSize: <Text {...{ required: true, help: true }} />,
    configLogsCleanupDuration: <Text {...{ required: true, help: true }} />,
    configLogsCleanupSize: <Text {...{ required: true, help: true }} />,
    vpnLogsCleanupDuration: <Text {...{ required: true, help: true }} />,
    vpnLogsCleanupSize: <Text {...{ required: true, help: true }} />,
    deviceFailedLoginAttemptsCleanupDuration: <Text {...{ required: true, help: true }} />,
    deviceFailedLoginAttemptsCleanupSize: <Text {...{ required: true, help: true }} />,
    userLoginAttemptsCleanupDuration: <Text {...{ required: true, help: true }} />,
    userLoginAttemptsCleanupSize: <Text {...{ required: true, help: true }} />,
    deviceCommandsCleanupDuration: <Text {...{ required: true, help: true }} />,
    deviceCommandsCleanupSize: <Text {...{ required: true, help: true }} />,
    maintenanceLogsCleanupDuration: <Text {...{ required: true, help: true }} />,
    maintenanceLogsCleanupSize: <Text {...{ required: true, help: true }} />,
    importFileRowLogsCleanupDuration: <Text {...{ required: true, help: true }} />,
    importFileRowLogsCleanupSize: <Text {...{ required: true, help: true }} />,
    auditLogsCleanupDuration: <Text {...{ required: true, help: true }} />,
    auditLogsCleanupSize: <Text {...{ required: true, help: true }} />,
};

export default getFields(fields);
