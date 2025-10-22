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
import { HistoryOutlined } from "@mui/icons-material";
import getFields from "~app/entities/Configuration/logsFields";
import ConfigurationChange, { ConfigurationChangeInterface } from "~app/routes/Configuration/ConfigurationChange";
import { useUser } from "~app/contexts/User";

const logsConfiguration: ConfigurationChangeInterface = {
    endpoint: "/logs",
    title: "logs",
    to: "/logs",
    icon: <HistoryOutlined />,
};

const Logs = () => {
    const { isAccessGranted } = useUser();

    const scepFieldNames = ["vpnLogsCleanupDuration", "vpnLogsCleanupSize"];

    const fields = getFields(undefined, isAccessGranted({ adminScep: true }) ? undefined : scepFieldNames);

    return <ConfigurationChange {...{ configuration: logsConfiguration, fields }} />;
};

export default Logs;
export { logsConfiguration };
