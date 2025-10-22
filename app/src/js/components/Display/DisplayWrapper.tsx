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

interface DisplayWrapperProps {
    children: React.ReactNode;
}

const DisplayWrapper = ({ children }: DisplayWrapperProps) => {
    return (
        <Box
            {...{
                sx: {
                    display: "flex",
                    flexDirection: "column",
                    borderWidth: "1px",
                    borderStyle: "solid",
                    borderColor: "grey.300",
                    borderRadius: 0.5,
                    fontSize: 14,
                    overflow: "hidden",
                },
            }}
        >
            {children}
        </Box>
    );
};

export default DisplayWrapper;
export { DisplayWrapperProps };
