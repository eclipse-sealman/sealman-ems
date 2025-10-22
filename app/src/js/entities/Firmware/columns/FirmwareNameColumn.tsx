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
import { CloudUploadOutlined, LinkOutlined } from "@mui/icons-material";
import { Box, Tooltip } from "@mui/material";
import { useTranslation } from "react-i18next";
import { getIn } from "formik";
import { getFeatureName } from "~app/entities/Firmware/utilities";

const FirmwareNameColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    const { t } = useTranslation();

    if (typeof columnName === "undefined") {
        throw new Error("FirmwareNameColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("FirmwareNameColumn component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;
    const featureName = getFeatureName(value.deviceType, value.feature);
    const sourceType = value.sourceType;

    let sourceIcon = null;

    if (sourceType === "upload") {
        sourceIcon = (
            <Tooltip {...{ title: t("firmwareName.tooltip.sourceType.upload") }}>
                <CloudUploadOutlined {...{ sx: { fontSize: 19 } }} />
            </Tooltip>
        );
    }

    if (sourceType === "externalUrl") {
        sourceIcon = (
            <Tooltip {...{ title: t("firmwareName.tooltip.sourceType.externalUrl") }}>
                <LinkOutlined {...{ sx: { fontSize: 19 } }} />
            </Tooltip>
        );
    }

    return (
        <>
            {value?.name}
            <Box {...{ sx: { display: "flex", fontSize: 13, alignItems: "center", gap: 0.5 } }}>
                <span>({featureName})</span>
                {sourceIcon}
            </Box>
        </>
    );
};

export default FirmwareNameColumn;
export { ColumnPathInterface as FirmwareNameColumnProps };
