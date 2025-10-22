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
import { parseStatus as parseStatusEnum, ParseStatusType } from "~app/entities/ImportFileRow/enums";
import { CheckCircleOutlined, ErrorOutlineOutlined, WarningAmberOutlined } from "@mui/icons-material";
import TableImportFileRowLog from "~app/components/Common/TableImportFileRowLog";

const ImportFileRowParseStatusColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("ImportFileRowParseStatusColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("ImportFileRowParseStatusColumn component: Missing required result prop");
    }

    const row = path ? getIn(result, path) : result;
    const parseStatus: undefined | ParseStatusType = row?.parseStatus;
    if (!parseStatus) {
        return null;
    }

    let icon;

    switch (parseStatus) {
        case "valid":
            icon = <CheckCircleOutlined {...{ color: "success" }} />;
            break;
        case "warning":
            icon = <WarningAmberOutlined {...{ color: "warning" }} />;
            break;
        case "invalid":
            icon = <ErrorOutlineOutlined {...{ color: "error" }} />;
            break;
    }

    if (parseStatus === "valid") {
        return (
            <IconButton
                {...{
                    icon,
                    disabled: true,
                    tooltip: parseStatusEnum.getLabel(parseStatus),
                    size: "small",
                }}
            />
        );
    }

    return (
        <IconButtonDialog
            {...{
                icon,
                tooltip: parseStatusEnum.getLabel(parseStatus),
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

export default ImportFileRowParseStatusColumn;
export { ColumnPathInterface as ImportFileRowParseStatusColumnProps };
