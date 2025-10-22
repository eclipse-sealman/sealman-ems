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
import { ResultRedirectTableQuery, ResultRedirectTableQueryProps } from "@arteneo/forge";
import { HistoryOutlined } from "@mui/icons-material";

const RedirectLogs = (props: ResultRedirectTableQueryProps) => {
    return (
        <ResultRedirectTableQuery
            {...{
                label: "action.logs",
                color: "info",
                size: "small",
                variant: "contained",
                startIcon: <HistoryOutlined />,
                ...props,
            }}
        />
    );
};

export default RedirectLogs;
export { ResultRedirectTableQueryProps as RedirectLogsProps };
