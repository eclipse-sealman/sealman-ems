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
import { ColumnPathInterface } from "@arteneo/forge";
import { Box } from "@mui/material";
import { getIn } from "formik";
import FirmwareHardwareFilesIcon from "~app/entities/Firmware/components/FirmwareHardwareFilesIcon";

const FirmwareNameColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("FirmwareNameColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("FirmwareNameColumn component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;

    return (
        <Box {...{ sx: { display: "flex", alignItems: "center", gap: 0.5 } }}>
            {value?.representation}
            <FirmwareHardwareFilesIcon {...{ firmware: value }} />
        </Box>
    );
};

export default FirmwareNameColumn;
export { ColumnPathInterface as FirmwareNameColumnProps };
