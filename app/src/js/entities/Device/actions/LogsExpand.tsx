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
import { DeviceInterface } from "~app/entities/Device/definitions";
import ResultButtonExpand from "~app/components/Common/ResultButtonExpand";
import RedirectLogs from "~app/components/Table/actions/RedirectLogs";

interface LogsExpandProps {
    result?: DeviceInterface;
}

const LogsExpand = ({ result }: LogsExpandProps) => {
    if (typeof result === "undefined") {
        throw new Error("LogsExpand component: Missing required result prop");
    }

    return (
        <ResultButtonExpand
            {...{
                result,
                deny: result?.deny,
                denyKey: "logs",
                denyBehavior: "hide",
                label: "action.logs",
                startIcon: <HistoryOutlined />,
            }}
        >
            <RedirectLogs
                {...{
                    label: "action.communicationLog",
                    to: "/communicationlog/list",
                    deny: result?.deny,
                    denyKey: "communicationLogs",
                    denyBehavior: "hide",
                    filters: (result) => ({
                        device: result.id,
                    }),
                }}
            />
            <RedirectLogs
                {...{
                    label: "action.deviceCommand",
                    to: "/devicecommand/list",
                    deny: result?.deny,
                    denyKey: "deviceCommands",
                    denyBehavior: "hide",
                    filters: (result) => ({
                        device: result.id,
                    }),
                }}
            />
            <RedirectLogs
                {...{
                    label: "action.configLog",
                    to: "/configlog/list",
                    deny: result?.deny,
                    denyKey: "configLogs",
                    denyBehavior: "hide",
                    filters: (result) => ({
                        device: result.id,
                    }),
                }}
            />
            <RedirectLogs
                {...{
                    label: "action.diagnoseLog",
                    to: "/diagnoselog/list",
                    deny: result?.deny,
                    denyKey: "diagnoseLogs",
                    denyBehavior: "hide",
                    filters: (result) => ({
                        device: result.id,
                    }),
                }}
            />
            <RedirectLogs
                {...{
                    label: "action.vpnLog",
                    to: "/vpnlog/list",
                    deny: result?.deny,
                    denyKey: "vpnLogs",
                    denyBehavior: "hide",
                    filters: (result) => ({
                        device: result.id,
                    }),
                }}
            />
            <RedirectLogs
                {...{
                    label: "action.secretLog",
                    to: "/secretlog/list",
                    deny: result?.deny,
                    denyKey: "secretLogs",
                    denyBehavior: "hide",
                    filters: (result) => ({
                        device: result.id,
                    }),
                }}
            />
        </ResultButtonExpand>
    );
};

export default LogsExpand;
export { LogsExpandProps };
