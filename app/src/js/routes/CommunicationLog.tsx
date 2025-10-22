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
import getColumns from "~app/entities/CommunicationLog/columns";
import getFilters from "~app/entities/CommunicationLog/filters";
import Builder from "~app/components/Crud/Builder";
import { ExportQueryFieldInterface } from "@arteneo/forge";

const CommunicationLog = () => {
    const columns = getColumns();
    const filters = getFilters();

    const modifyFields = (fields: ExportQueryFieldInterface[]) => {
        const deviceIndex = fields.findIndex((field) => field.field === "device");

        if (deviceIndex !== -1) {
            fields.splice(deviceIndex, 0, {
                field: "deviceType",
                label: "label.deviceType",
            });
        }

        const cellularUptimeSeconds1Index = fields.findIndex((field) => field.field === "cellularUptimeSeconds1");

        if (cellularUptimeSeconds1Index !== -1) {
            fields.splice(cellularUptimeSeconds1Index, 1, {
                field: "cellularUptimeSeconds1",
                label: "label.cellularUptimeSecondsExport1",
            });
        }

        const cellularUptimeSeconds2Index = fields.findIndex((field) => field.field === "cellularUptimeSeconds2");

        if (cellularUptimeSeconds2Index !== -1) {
            fields.splice(cellularUptimeSeconds2Index, 1, {
                field: "cellularUptimeSeconds2",
                label: "label.cellularUptimeSecondsExport2",
            });
        }

        return fields;
    };

    return (
        <Builder
            {...{
                endpointPrefix: "/communicationlog",
                title: "route.title.communicationLog",
                icon: <HistoryOutlined />,
                listProps: {
                    columns,
                    filters,
                    hasExportCsv: true,
                    exportCsvProps: {
                        modifyFields,
                    },
                    hasExportExcel: true,
                    exportExcelProps: {
                        modifyFields,
                    },
                    defaultSorting: {
                        createdAt: "desc",
                    },
                    visibleColumnsKey: "communicationLog",
                    defaultColumns: [
                        "logLevel",
                        "device",
                        "message",
                        "serialNumber",
                        "firmwareVersion1",
                        "createdAt",
                        "actions",
                    ],
                },
                deleteProps: {},
                duplicateProps: {},
            }}
        />
    );
};

export default CommunicationLog;
