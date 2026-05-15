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
import { getFeatureName } from "~app/entities/Firmware/utilities";
import FirmwareSourceTypeColumn from "~app/entities/Firmware/columns/FirmwareSourceTypeColumn";
import FirmwareNameColumn from "~app/entities/Firmware/columns/FirmwareNameColumn";

const FirmwareNameExtendedColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("FirmwareNameExtendedColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("FirmwareNameExtendedColumn component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;
    const featureName = getFeatureName(value.deviceType, value.feature);

    return (
        <>
            <FirmwareNameColumn {...{ result, columnName, path }} />
            <Box {...{ sx: { display: "flex", fontSize: 13, alignItems: "center", gap: 0.5 } }}>
                <span>({featureName})</span>
                <FirmwareSourceTypeColumn {...{ result, columnName, path }} />
            </Box>
        </>
    );
};

export default FirmwareNameExtendedColumn;
export { ColumnPathInterface as FirmwareNameExtendedColumnProps };
