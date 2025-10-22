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
import DisplaySecretVariableTitle, {
    DisplaySecretVariableTitleProps,
} from "~app/components/Display/DisplaySecretVariableTitle";

interface DisplaySecretVariableRowProps extends DisplaySecretVariableTitleProps {
    children: React.ReactNode;
}

const DisplaySecretVariableRow = ({ children, ...props }: DisplaySecretVariableRowProps) => {
    return (
        <Box
            {...{
                className: "MuiDisplayRow-root",
                sx: {
                    display: "grid",
                    gridTemplateColumns: {
                        xs: "300px 1fr",
                        sm: "450px 1fr",
                    },
                    alignItems: "center",
                    alignContent: "flex-start",
                    "& + .MuiDisplayRow-root": {
                        borderTopWidth: "1px",
                        borderTopStyle: "solid",
                        borderTopColor: "grey.300",
                    },
                },
            }}
        >
            <DisplaySecretVariableTitle {...props} />
            <Box {...{ sx: { py: 0.5, px: 1, display: "flex", overflow: "hidden" } }}>{children}</Box>
        </Box>
    );
};

export default DisplaySecretVariableRow;
export { DisplaySecretVariableRowProps };
