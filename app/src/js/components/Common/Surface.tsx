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
import { Box, Divider, Paper } from "@mui/material";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";

interface SurfaceProps extends SurfaceTitleProps {
    children: React.ReactNode;
}

const Surface = ({ title, subtitle, hint, icon, children, ...surfaceTitleProps }: SurfaceProps) => {
    const hasTitle =
        typeof title !== "undefined" ||
        typeof subtitle !== "undefined" ||
        typeof hint !== "undefined" ||
        typeof icon !== "undefined";

    return (
        <Paper {...{ sx: { p: { xs: 2, sm: 3 } } }}>
            {hasTitle && (
                <Box {...{ sx: { mt: -0.5, mb: { xs: 2, sm: 3 } } }}>
                    <SurfaceTitle {...{ title, subtitle, hint, icon, disableBackButton: true, ...surfaceTitleProps }} />
                    <Divider {...{ sx: { borderColor: "#e9e9e9", borderBottomWidth: 2, borderRadius: "15px" } }} />
                </Box>
            )}
            {children}
        </Paper>
    );
};

export default Surface;
export { SurfaceProps };
