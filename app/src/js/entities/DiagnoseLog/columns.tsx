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
import { ActionsColumn, getColumns } from "@arteneo/forge";
import LogLevelColumn from "~app/components/Table/columns/LogLevelColumn";
import ResultDialogContent from "~app/components/Table/actions/ResultDialogContent";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";
import VpnDeviceColumn from "~app/components/Table/columns/VpnDeviceColumn";

const columns = {
    logLevel: <LogLevelColumn />,
    device: <VpnDeviceColumn />,
    createdAt: <CreatedAtByColumn />,
    actions: (
        <ActionsColumn>
            <ResultDialogContent
                {...{
                    denyKey: "showContent",
                    denyBehavior: "hide",
                    dialogProps: (result) => ({
                        initializeEndpoint: "/diagnoselog/content/" + result.id,
                        content: (payload) => payload?.content,
                    }),
                }}
            />
        </ActionsColumn>
    ),
};

export default getColumns(columns);
