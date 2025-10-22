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
import getColumns from "~app/entities/VpnLog/columns";
import getFilters from "~app/entities/VpnLog/filters";
import Builder from "~app/components/Crud/Builder";
import { ExportQueryFieldInterface } from "@arteneo/forge";
import { useUser } from "~app/contexts/User";

const VpnLog = () => {
    const { isAccessGranted } = useUser();
    const columns = getColumns();
    const filters = getFilters(undefined, !isAccessGranted({ admin: true }) ? ["user"] : undefined);

    const modifyFields = (fields: ExportQueryFieldInterface[]) => {
        const targetIndex = fields.findIndex((field) => field.field === "target");

        if (targetIndex !== -1) {
            fields.splice(
                targetIndex,
                1,
                {
                    field: "user",
                    label: "label.user",
                },
                {
                    field: "deviceType",
                    label: "label.deviceType",
                },
                {
                    field: "device",
                    label: "label.device",
                },
                {
                    field: "endpointDevice",
                    label: "label.endpointDevice",
                }
            );
        }

        return fields;
    };

    return (
        <Builder
            {...{
                endpointPrefix: "/vpnlog",
                title: "route.title.vpnLog",
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
                },
                deleteProps: {},
                duplicateProps: {},
            }}
        />
    );
};

export default VpnLog;
