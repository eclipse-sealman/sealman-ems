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
import { Box } from "@mui/material";
import { useDialog } from "@arteneo/forge";
import DisplaySecretVariables from "~app/components/Display/DisplaySecretVariables";
import VariableExampleValueColumn from "~app/components/Table/columns/VariableExampleValueColumn";

const DialogShowDeviceSecretVariables = () => {
    const { payload } = useDialog();

    return (
        <Box
            {...{
                sx: {
                    display: "grid",
                    alignItems: "flex-start",
                    gap: { xs: 2, lg: 4 },
                    mb: 2,
                },
            }}
        >
            {payload?.encodedVariables && (
                <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 2 } }}>
                    <DisplaySecretVariables
                        {...{
                            variables: payload?.encodedVariables,
                            collapseRowsAbove: 12,
                            variableValueComponent: <VariableExampleValueColumn />,
                        }}
                    />
                </Box>
            )}
        </Box>
    );
};

export default DialogShowDeviceSecretVariables;
