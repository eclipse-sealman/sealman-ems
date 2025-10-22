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
import { Paper } from "@mui/material";

interface DisplaySurfacePaperProps {
    children: React.ReactNode;
}

const DisplaySurfacePaper = ({ children }: DisplaySurfacePaperProps) => {
    return <Paper {...{ sx: { px: { xs: 2, sm: 3 }, py: { xs: 1, sm: 2 } } }}>{children}</Paper>;
};

export default DisplaySurfacePaper;
export { DisplaySurfacePaperProps };
