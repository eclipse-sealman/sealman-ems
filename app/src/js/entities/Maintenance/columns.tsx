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
import { ActionsColumn, getColumns, TextColumn } from "@arteneo/forge";
import { status } from "~app/entities/Maintenance/enums";
import MaintenanceNameColumn from "~app/entities/Maintenance/columns/MaintenanceNameColumn";
import DateTimeSecondsColumn from "~app/components/Table/columns/DateTimeSecondsColumn";
import EnumColorColumn from "~app/components/Table/columns/EnumColorColumn";
import ResultDelete from "~app/components/Table/actions/ResultDelete";
import RedirectLogs from "~app/components/Table/actions/RedirectLogs";
import MaintenanceDownload from "~app/entities/Maintenance/actions/MaintenanceDownload";

const columns = {
    id: <TextColumn />,
    name: <MaintenanceNameColumn {...{ disableSorting: true }} />,
    status: <EnumColorColumn {...{ enum: status }} />,
    updatedAt: <DateTimeSecondsColumn />,
    createdAt: <DateTimeSecondsColumn />,
    actions: (
        <ActionsColumn>
            <MaintenanceDownload />
            <RedirectLogs
                {...{
                    to: "/maintenance/logs",
                    filters: (result) => ({
                        maintenanceId: result?.id,
                    }),
                }}
            />
            <ResultDelete
                {...{
                    dialogProps: (result) => ({
                        confirmProps: {
                            endpoint: "/maintenance/" + result.id,
                        },
                        boldLabel:
                            result.status === "success" && result.type === "backup"
                                ? "resultDelete.boldLabel.maintenanceBackup"
                                : undefined,
                    }),
                }}
            />
        </ActionsColumn>
    ),
};

export default getColumns(columns);
