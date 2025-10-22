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
import { VpnKeyOutlined } from "@mui/icons-material";
import getColumns from "~app/entities/SecretLog/columns";
import getFilters from "~app/entities/SecretLog/filters";
import Builder from "~app/components/Crud/Builder";
import { ExportQueryFieldInterface } from "@arteneo/forge";

const SecretLog = () => {
    const columns = getColumns();
    const filters = getFilters();

    const modifyFields = (fields: ExportQueryFieldInterface[]) => {
        fields.push({
            field: "createdBy",
            label: "label.createdBy",
        });

        return fields;
    };

    return (
        <Builder
            {...{
                endpointPrefix: "/secretlog",
                title: "route.title.secretLog",
                icon: <VpnKeyOutlined />,
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
                    additionalSorting: {
                        id: "desc",
                    },
                },
                deleteProps: {},
                duplicateProps: {},
            }}
        />
    );
};

export default SecretLog;
