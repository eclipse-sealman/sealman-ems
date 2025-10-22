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
import DisplayRowTitle, { DisplayRowTitleProps } from "~app/components/Display/DisplayRowTitle";

interface DisplayRowProps extends DisplayRowTitleProps {
    children: React.ReactNode;
}

const DisplayRow = ({ children, ...props }: DisplayRowProps) => {
    return (
        <Box
            {...{
                className: "MuiDisplayRow-root",
                sx: {
                    display: "grid",
                    gridTemplateColumns: {
                        xs: "120px 1fr",
                        sm: "180px 1fr",
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
            <DisplayRowTitle {...props} />
            <Box {...{ sx: { py: 0.5, px: 1, display: "flex", overflow: "hidden" } }}>{children}</Box>
        </Box>
    );
};

export default DisplayRow;
export { DisplayRowProps };
