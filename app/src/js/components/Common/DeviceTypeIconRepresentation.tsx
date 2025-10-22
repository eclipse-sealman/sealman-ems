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
import { Box } from "@mui/system";
import {
    ApiOutlined,
    CellTowerOutlined,
    CloudSyncOutlined,
    ComputerOutlined,
    DeviceHubOutlined,
    DevicesOutlined,
    HubOutlined,
    LinkOffOutlined,
    PersonOutlined,
    RouterOutlined,
    SearchOffOutlined,
    SettingsInputAntennaOutlined,
    StarOutlined,
} from "@mui/icons-material";
import { Badge, Icon, Tooltip } from "@mui/material";
import { Translation } from "react-i18next";

interface DeviceTypeIconRepresentationProps {
    representation: string;
    icon?: string;
    color?: string;
    isAvailable?: boolean;
    enabled?: boolean;
}

//Removed from component because it is used in SelectDeviceTypeIcon and DeviceTypeFilter
const renderIcon = (icon?: string, color = "inherit", isAvailable = true, enabled = true) => {
    const iconComponent = (
        <Icon fontSize="small" sx={{ display: "flex" }}>
            {getIconComponent(icon, "small")}
        </Icon>
    );
    let tooltipContent = "";

    if (!isAvailable) {
        tooltipContent = "deviceTypeIconRepresentation.notAvailable";
    }

    if (!enabled) {
        tooltipContent = "deviceTypeIconRepresentation.disabled";
    }

    return (
        <Box
            sx={{
                border: "2px solid " + color,
                borderRadius: "50%",
                padding: "2px",
                display: "flex",
                alignContent: "center",
                justifyContent: "center",
                marginRight: "2px",
            }}
        >
            {isAvailable ? (
                iconComponent
            ) : (
                <Tooltip title={<Translation>{(t) => t(tooltipContent)}</Translation>} placement={"right-end"}>
                    <Badge color="error" badgeContent={"!"}>
                        {iconComponent}
                    </Badge>
                </Tooltip>
            )}
        </Box>
    );
};

const getIconComponent = (icon?: string, fontSize: "small" | "inherit" | "medium" | "large" | undefined = "small") => {
    switch (icon) {
        case "router":
            return <RouterOutlined fontSize={fontSize} />;
        case "star":
            return <StarOutlined fontSize={fontSize} />;
        case "computer":
            return <ComputerOutlined fontSize={fontSize} />;
        case "devices":
            return <DevicesOutlined fontSize={fontSize} />;
        case "cellTower":
            return <CellTowerOutlined fontSize={fontSize} />;
        case "linkOff":
            return <LinkOffOutlined fontSize={fontSize} />;
        case "api":
            return <ApiOutlined fontSize={fontSize} />;
        case "hub":
            return <HubOutlined fontSize={fontSize} />;
        case "settingsInputAntenna":
            return <SettingsInputAntennaOutlined fontSize={fontSize} />;
        case "cloudSync":
            return <CloudSyncOutlined fontSize={fontSize} />;
        case "deviceHub":
            return <DeviceHubOutlined fontSize={fontSize} />;
        case "search":
            return <SearchOffOutlined fontSize={fontSize} />;
        //This icon is used only for users - it is not added to deviceType icon enum
        case "user":
            return <PersonOutlined fontSize={fontSize} />;
        default:
            return <></>;
    }
};

//Removed from column component because it is used in SelectDeviceTypeIcon and DeviceTypeFilter
const DeviceTypeIconRepresentation = ({
    representation,
    icon,
    color = "#000000",
    isAvailable = true,
    enabled = true,
}: DeviceTypeIconRepresentationProps) => {
    return (
        <Box sx={{ alignItems: "center", display: "flex", opacity: isAvailable ? "inherit" : "50%" }}>
            {renderIcon(icon, color, isAvailable, enabled)}&nbsp;{representation}
        </Box>
    );
};

export default DeviceTypeIconRepresentation;
export { DeviceTypeIconRepresentationProps, renderIcon };
