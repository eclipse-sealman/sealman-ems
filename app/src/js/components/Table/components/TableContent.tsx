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
import { Box, Paper } from "@mui/material";
import { TableResults, TableToolbar } from "@arteneo/forge";
import TableFilters from "~app/components/Table/components/TableFilters";

const TableContent = () => {
    return (
        <>
            <TableFilters />
            <Paper sx={{ px: { xs: 2, md: 3 }, pt: 3, pb: 3 }}>
                <TableToolbar />
                <Box {...{ sx: { overflowX: "auto" } }}>
                    <TableResults />
                </Box>
            </Paper>
        </>
    );
};

export default TableContent;
