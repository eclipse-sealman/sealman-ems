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
    ExportExcel as ForgeExportExcel,
    ExportExcelProps as ForgeExportExcelProps,
    Optional,
    TranslateVariablesInterface,
    ExportQueryFieldInterface,
} from "@arteneo/forge";
import { DownloadOutlined } from "@mui/icons-material";
import { format } from "date-fns";
import { useTranslation } from "react-i18next";

interface ExportExcelProps extends Optional<ForgeExportExcelProps, "filename" | "sheetName"> {
    filenameLabel?: string;
    filenameLabelVariables?: TranslateVariablesInterface;
    sheetNameLabel?: string;
    sheetNameLabelVariables?: TranslateVariablesInterface;
    labelTitle?: string;
    labelTitleVariables?: TranslateVariablesInterface;
}

const ExportExcel = ({
    filename,
    filenameLabel = "exportExcel.filename",
    filenameLabelVariables = {},
    sheetName,
    sheetNameLabel = "exportExcel.sheetName",
    sheetNameLabelVariables = {},
    labelTitle,
    labelTitleVariables = {},
    modifyFields,
    ...props
}: ExportExcelProps) => {
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

    let resolvedSheetName: undefined | string = sheetName;

    if (typeof resolvedSheetName === "undefined") {
        const defaultLabelVariables: TranslateVariablesInterface = {};

        if (typeof labelTitle !== "undefined") {
            defaultLabelVariables.title = t(labelTitle, labelTitleVariables);
        }

        resolvedSheetName = t(sheetNameLabel, Object.assign(defaultLabelVariables, sheetNameLabelVariables)) ?? "";
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
        <ForgeExportExcel
            {...{
                color: "info",
                startIcon: <DownloadOutlined />,
                filename: resolvedFilename,
                sheetName: resolvedSheetName,
                modifyFields: internalModifyFields,
                ...props,
            }}
        />
    );
};

export default ExportExcel;
export { ExportExcelProps };
