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
import { MemoryOutlined } from "@mui/icons-material";
import { ExportQueryFieldInterface } from "@arteneo/forge";
import getColumns from "~app/entities/Firmware/columns";
import getFilters from "~app/entities/Firmware/filters";
import Builder from "~app/components/Crud/Builder";
import { useUser } from "~app/contexts/User";

const Firmware = () => {
    const { isAccessGranted } = useUser();
    const columns = getColumns();
    const filters = getFilters(undefined, !isAccessGranted({ admin: true }) ? ["updatedBy", "createdBy"] : undefined);

    const modifyFields = (fields: ExportQueryFieldInterface[]) => {
        const nameIndex = fields.findIndex((field) => field.field === "name");

        if (nameIndex !== -1) {
            fields.splice(nameIndex + 1, 0, {
                field: "sourceType",
                label: "label.sourceType",
            });
        }

        return fields;
    };

    return (
        <Builder
            {...{
                endpointPrefix: "/firmware",
                title: "route.title.firmware",
                icon: <MemoryOutlined />,
                listProps: {
                    columns,
                    filters,
                    hasCreate: true,
                    hasEdit: true,
                    hasDuplicate: true,
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
            }}
        />
    );
};

export default Firmware;
