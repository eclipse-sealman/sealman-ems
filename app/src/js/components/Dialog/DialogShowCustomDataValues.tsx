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
import DisplayVariables from "~app/components/Display/DisplayVariables";
import { CustomDataValuesInterface } from "~app/entities/Common/definitions";

const DialogShowCustomDataValues = () => {
    const { payload } = useDialog();

    const responseCustomDataValues = payload as CustomDataValuesInterface[];
    const variables = responseCustomDataValues.map(({ name, value }) => ({
        name: name,
        variableValue: value,
    }));

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
            <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 2 } }}>
                <DisplayVariables {...{ variables, collapseRowsAbove: 24 }} />
            </Box>
        </Box>
    );
};

export default DialogShowCustomDataValues;
