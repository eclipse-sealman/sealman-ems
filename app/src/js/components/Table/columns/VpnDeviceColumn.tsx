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
import { Box, Tooltip, Link as MuiLink } from "@mui/material";
import DeviceTypeIconRepresentation from "~app/components/Common/DeviceTypeIconRepresentation";
import { useTranslation } from "react-i18next";
import { getIn } from "formik";
import { Link } from "react-router-dom";

const VpnDeviceColumn = ({ path, result, columnName }: ColumnPathInterface) => {
    const { t } = useTranslation();

    if (typeof columnName === "undefined") {
        throw new Error("VpnDeviceColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("VpnDeviceColumn component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;

    if (!value?.device) {
        return null;
    }

    const target = value?.device;
    const link = "/device/details/" + target.id;
    const icon = target?.deviceType?.icon;
    const color = target?.deviceType?.color;
    const isAvailable = target?.deviceType?.isAvailable;
    const enabled = target?.deviceType?.enabled;
    const deviceType = target?.deviceType?.representation;
    const ip = target?.virtualIp;
    const tooltipContent = (
        <Box>
            <Box>
                {t("label.device")}:&nbsp;{target?.representation}
            </Box>
            <Box>
                {t("label.deviceType")}:&nbsp;{deviceType}
            </Box>
            {target?.vpnIp && (
                <Box>
                    {t("label.vpnIp")}:&nbsp;{target?.vpnIp}
                </Box>
            )}
            {ip && (
                <Box>
                    {t("label.virtualIp")}:&nbsp;{ip}
                </Box>
            )}
        </Box>
    );

    const content = (
        <Box>
            <Box {...{ sx: { whiteSpace: "nowrap" } }}>
                <MuiLink {...{ as: Link, to: link, color: "inherit" }}>
                    <DeviceTypeIconRepresentation
                        representation={target?.representation}
                        icon={icon}
                        color={color}
                        isAvailable={isAvailable}
                        enabled={enabled}
                    />
                </MuiLink>
            </Box>
            {typeof ip !== "undefined" && <Box {...{ sx: { fontSize: 13 } }}>({ip})</Box>}
        </Box>
    );

    return <Tooltip title={tooltipContent}>{content}</Tooltip>;
};

export default VpnDeviceColumn;
export { ColumnPathInterface as VpnDeviceColumnProps };
