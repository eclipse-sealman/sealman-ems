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
import { getIn } from "formik";
import { VisibilityOutlined } from "@mui/icons-material";
import { ColumnPathInterface, IconButtonDialog } from "@arteneo/forge";
import DisplayVariables from "~app/components/Display/DisplayVariables";
import EntityVariableInterface from "~app/definitions/EntityVariableInterface";
import { Box } from "@mui/material";

const VariablesColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("VariablesColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("VariablesColumn component: Missing required result prop");
    }

    const variables: EntityVariableInterface[] = getIn(result, path ? path : columnName, []);
    if (variables.length === 0) {
        return null;
    }

    return (
        <Box {...{ component: "span", sx: { display: "flex", alignItems: "center", gap: 1 } }}>
            {variables.map((variable) => variable.name).join(", ")}
            <IconButtonDialog
                {...{
                    size: "small",
                    color: "info",
                    icon: <VisibilityOutlined {...{ fontSize: "small", sx: { fontSize: 15 } }} />,
                    tooltip: "variablesColumn.view",
                    sx: {
                        p: 0.25,
                        ml: 0.5,
                    },
                    dialogProps: {
                        title: "variablesColumn.view",
                        children: <DisplayVariables {...{ variables }} />,
                    },
                }}
            />
        </Box>
    );
};

export default VariablesColumn;
export { ColumnPathInterface as VariablesColumnProps };
