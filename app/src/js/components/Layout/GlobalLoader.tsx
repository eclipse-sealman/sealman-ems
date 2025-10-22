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
import { useLoader } from "@arteneo/forge";
import { Box, LinearProgress } from "@mui/material";

const GlobalLoader = () => {
    const { visibleGlobalLoader } = useLoader();

    if (!visibleGlobalLoader) {
        return null;
    }

    return (
        <Box sx={{ position: "absolute", zIndex: 20, top: 0, left: 0, width: "100%" }}>
            <LinearProgress sx={{ height: 3 }} />
        </Box>
    );
};

export default GlobalLoader;
