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
import { DeveloperBoardOutlined } from "@mui/icons-material";
import getColumns from "~app/entities/OpenSourceLicense/columns";
import getFilters from "~app/entities/OpenSourceLicense/filters";
import Builder from "~app/components/Crud/Builder";
import { ExportQueryFieldInterface } from "@arteneo/forge";
import BuilderToolbar from "~app/components/Table/toolbar/BuilderToolbar";
import DownloadTxt from "~app/entities/OpenSourceLicense/toolbar/DownloadTxt";

const OpenSourceLicense = () => {
    const columns = getColumns();
    const filters = getFilters();

    const modifyFields = (fields: ExportQueryFieldInterface[]) => {
        const createdAtIndex = fields.findIndex((field) => field.field === "createdAt");

        if (createdAtIndex !== -1) {
            fields.splice(createdAtIndex, 0, {
                field: "licenseContent",
                label: "label.licenseContent",
            });
        }
        return fields;
    };

    return (
        <Builder
            {...{
                endpointPrefix: "/opensourcelicense",
                title: "route.title.openSourceLicense",
                icon: <DeveloperBoardOutlined />,
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
                        name: "asc",
                    },
                    toolbar: (
                        <BuilderToolbar
                            {...{
                                render: ({ exportCsvAction, exportExcelAction }) => (
                                    <>
                                        <DownloadTxt />
                                        {exportCsvAction}
                                        {exportExcelAction}
                                    </>
                                ),
                            }}
                        />
                    ),
                },
            }}
        />
    );
};

export default OpenSourceLicense;
