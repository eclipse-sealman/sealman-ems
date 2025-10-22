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
import { ColumnPathInterface, IconButton, IconButtonDialog } from "@arteneo/forge";
import { getIn } from "formik";
import { importStatus as importStatusEnum, ImportStatusType } from "~app/entities/ImportFileRow/enums";
import { CheckCircleOutlined, ErrorOutlineOutlined, WarningAmberOutlined } from "@mui/icons-material";
import TableImportFileRowLog from "~app/components/Common/TableImportFileRowLog";

const ImportFileRowImportStatusColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("ImportFileRowImportStatusColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("ImportFileRowImportStatusColumn component: Missing required result prop");
    }

    const row = path ? getIn(result, path) : result;
    const importStatus: undefined | ImportStatusType = row?.importStatus;
    if (!importStatus) {
        return null;
    }

    let icon;

    switch (importStatus) {
        case "success":
            icon = <CheckCircleOutlined {...{ color: "success" }} />;
            break;
        case "warning":
            icon = <WarningAmberOutlined {...{ color: "warning" }} />;
            break;
        case "error":
            icon = <ErrorOutlineOutlined {...{ color: "error" }} />;
            break;
    }

    if (importStatus === "success") {
        return (
            <IconButton
                {...{
                    icon,
                    disabled: true,
                    tooltip: importStatusEnum.getLabel(importStatus),
                    size: "small",
                }}
            />
        );
    }

    return (
        <IconButtonDialog
            {...{
                icon,
                tooltip: importStatusEnum.getLabel(importStatus),
                size: "small",
                dialogProps: {
                    title: "importFile.log.dialog.title",
                    children: (
                        <TableImportFileRowLog
                            {...{
                                additionalFilters: {
                                    row: {
                                        filterBy: "row",
                                        filterType: "equal",
                                        filterValue: row.id,
                                    },
                                },
                            }}
                        />
                    ),
                    dialogProps: {
                        maxWidth: "lg",
                    },
                },
            }}
        />
    );
};

export default ImportFileRowImportStatusColumn;
export { ColumnPathInterface as ImportFileRowImportStatusColumnProps };
