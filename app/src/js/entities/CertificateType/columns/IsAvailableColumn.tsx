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
import { BooleanColumn, ColumnPathInterface } from "@arteneo/forge";
import { Box, Tooltip } from "@mui/material";
import { getIn } from "formik";
import { useTranslation } from "react-i18next";
import { HelpOutline } from "@mui/icons-material";

// Column act as BooleanColumn but on false shows tooltip with deny message
const IsAvailableColumn = ({ result, columnName, path, ...props }: ColumnPathInterface) => {
    const { t } = useTranslation();
    if (typeof columnName === "undefined") {
        throw new Error("IsAvailableColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("IsAvailableColumn component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : getIn(result, columnName);

    if (value) {
        return <BooleanColumn {...{ result, columnName, path, ...props }} />;
    }

    const denyMessage = result?.deny?.isAvailable ?? "label.unknown";

    return (
        <Tooltip title={t(denyMessage)}>
            <Box
                {...{
                    component: "span",
                }}
            >
                <BooleanColumn
                    {...{
                        result,
                        columnName,
                        path,
                        chipProps: { icon: <HelpOutline />, sx: { cursor: "help" } },
                        ...props,
                    }}
                />
            </Box>
        </Tooltip>
    );
};

export default IsAvailableColumn;
export { ColumnPathInterface as IsAvailableColumnProps };
