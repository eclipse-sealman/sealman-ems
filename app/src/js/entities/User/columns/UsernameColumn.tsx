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
import { getIn } from "formik";
import { ColumnPathInterface } from "@arteneo/forge";
import { useTranslation } from "react-i18next";
import { RadarOutlined, SyncLockOutlined } from "@mui/icons-material";
import { Box, Tooltip } from "@mui/material";

type UsernameColumnProps = ColumnPathInterface;

const UsernameColumn = ({ result, columnName, path }: UsernameColumnProps) => {
    const { t } = useTranslation();

    if (typeof columnName === "undefined") {
        throw new Error("UsernameColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("UsernameColumn component: Missing required result prop");
    }

    const user = path ? getIn(result, path) : result;
    if (!user) {
        return null;
    }

    return (
        <Box display="flex" alignItems="center" gap={1}>
            {user.representation}
            {user.ssoUser && (
                <Tooltip title={t("usernameColumn.tooltip.ssoUser", { username: user.username })}>
                    <SyncLockOutlined sx={{ fontSize: "1.25rem" }} />
                </Tooltip>
            )}
            {user.radiusUser && (
                <Tooltip title={t("usernameColumn.tooltip.radiusUser")}>
                    <RadarOutlined sx={{ fontSize: "1.25rem" }} />
                </Tooltip>
            )}
        </Box>
    );
};

export default UsernameColumn;
export { UsernameColumnProps };
