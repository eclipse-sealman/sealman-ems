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
import {
    ExportCsv as ForgeExportCsv,
    ExportCsvProps as ForgeExportCsvProps,
    Optional,
    TranslateVariablesInterface,
    ExportQueryFieldInterface,
} from "@arteneo/forge";
import { DownloadOutlined } from "@mui/icons-material";
import { format } from "date-fns";
import { useTranslation } from "react-i18next";

interface ExportCsvProps extends Optional<ForgeExportCsvProps, "filename"> {
    filenameLabel?: string;
    filenameLabelVariables?: TranslateVariablesInterface;
    labelTitle?: string;
    labelTitleVariables?: TranslateVariablesInterface;
}

const ExportCsv = ({
    filename,
    filenameLabel = "exportCsv.filename",
    filenameLabelVariables = {},
    labelTitle,
    labelTitleVariables = {},
    modifyFields,
    ...props
}: ExportCsvProps) => {
    const { t } = useTranslation();

    let resolvedFilename: undefined | string = filename;

    if (typeof resolvedFilename === "undefined") {
        const defaultLabelVariables: TranslateVariablesInterface = {
            date: format(new Date(), "yyyy_MM_dd_HH_mm_ss"),
        };

        if (typeof labelTitle !== "undefined") {
            defaultLabelVariables.title = t(labelTitle, labelTitleVariables);
        }

        resolvedFilename = t(filenameLabel, Object.assign(defaultLabelVariables, filenameLabelVariables)) ?? "";
    }

    const internalModifyFields = (fields: ExportQueryFieldInterface[]): ExportQueryFieldInterface[] => {
        const createdAtByIndex = fields.findIndex((field) => field.field === "createdAtBy");
        if (createdAtByIndex !== -1) {
            fields.splice(
                createdAtByIndex,
                1,
                { field: "createdAt", label: "label.createdAt" },
                { field: "createdBy", label: "label.createdBy" }
            );
        }

        const updatedAtByIndex = fields.findIndex((field) => field.field === "updatedAtBy");
        if (updatedAtByIndex !== -1) {
            fields.splice(
                updatedAtByIndex,
                1,
                { field: "updatedAt", label: "label.updatedAt" },
                { field: "updatedBy", label: "label.updatedBy" }
            );
        }

        if (typeof modifyFields !== "undefined") {
            return modifyFields(fields);
        }

        return fields;
    };

    return (
        <ForgeExportCsv
            {...{
                color: "info",
                startIcon: <DownloadOutlined />,
                filename: resolvedFilename,
                modifyFields: internalModifyFields,
                ...props,
            }}
        />
    );
};

export default ExportCsv;
export { ExportCsvProps };
