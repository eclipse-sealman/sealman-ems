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
import { Box, Tooltip } from "@mui/material";
import DeviceTypeIconRepresentation from "~app/components/Common/DeviceTypeIconRepresentation";
import { useTranslation } from "react-i18next";
import { getIn } from "formik";

const VpnUserColumn = ({ path, result, columnName }: ColumnPathInterface) => {
    const { t } = useTranslation();

    if (typeof columnName === "undefined") {
        throw new Error("VpnUserColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("VpnUserColumn component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;

    if (!value?.user) {
        return null;
    }

    const target = value?.user;
    const icon = "user";
    const ip = value?.user?.vpnIp;
    const tooltipContent = (
        <Box>
            <Box>
                {t("label.user")}:&nbsp;{target?.representation}
            </Box>
            {ip && (
                <Box>
                    {t("label.vpnIp")}:&nbsp;{ip}
                </Box>
            )}
        </Box>
    );

    const content = (
        <Box>
            <Box {...{ sx: { whiteSpace: "nowrap" } }}>
                <DeviceTypeIconRepresentation representation={target?.representation} icon={icon} />
            </Box>
            {typeof ip !== "undefined" && <Box {...{ sx: { fontSize: 13 } }}>({ip})</Box>}
        </Box>
    );

    return <Tooltip title={tooltipContent}>{content}</Tooltip>;
};

export default VpnUserColumn;
export { ColumnPathInterface as VpnUserColumnProps };
