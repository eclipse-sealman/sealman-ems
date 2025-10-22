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
import getColumns from "~app/entities/DeviceCommand/columns";
import getFilters from "~app/entities/DeviceCommand/filters";
import Builder from "~app/components/Crud/Builder";
import { ExportQueryFieldInterface } from "@arteneo/forge";

const DeviceCommand = () => {
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

        return fields;
    };

    return (
        <Builder
            {...{
                endpointPrefix: "/devicecommand",
                title: "route.title.deviceCommand",
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
                    visibleColumnsKey: "deviceCommand",
                    defaultColumns: [
                        "device",
                        "commandStatus",
                        "commandName",
                        "commandTransactionId",
                        "expireAt",
                        "createdAt",
                    ],
                },
                deleteProps: {},
                duplicateProps: {},
            }}
        />
    );
};

export default DeviceCommand;
