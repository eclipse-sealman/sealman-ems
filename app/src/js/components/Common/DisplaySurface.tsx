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
import DisplaySurfacePaper from "~app/components/Common/DisplaySurfacePaper";
import DisplaySurfaceTitle, { DisplaySurfaceTitleProps } from "~app/components/Common/DisplaySurfaceTitle";

interface DisplaySurfaceProps extends DisplaySurfaceTitleProps {
    children: React.ReactNode;
}

const DisplaySurface = ({ children, ...props }: DisplaySurfaceProps) => {
    return (
        <DisplaySurfacePaper>
            <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 2 } }}>
                <DisplaySurfaceTitle {...props} />
                {children}
            </Box>
        </DisplaySurfacePaper>
    );
};

export default DisplaySurface;
export { DisplaySurfaceProps };
