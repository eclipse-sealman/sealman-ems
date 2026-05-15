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
import { AddOutlined, MemoryOutlined } from "@mui/icons-material";
import { Box, Tooltip } from "@mui/material";
import { useTranslation } from "react-i18next";
import { FirmwareOptionsInterface } from "~app/entities/Firmware/definitions";

interface FirmwareHardwareFilesIconProps {
    firmware: FirmwareOptionsInterface;
}

const FirmwareHardwareFilesIcon = ({ firmware }: FirmwareHardwareFilesIconProps) => {
    const { t } = useTranslation();

    if (firmware?.enableHardwareFiles) {
        return (
            <Tooltip {...{ title: t("firmwareHardwareFiles.tooltip.hardwareFilesEnabled") }}>
                <Box {...{ sx: { display: "inline-flex", position: "relative" } }}>
                    <MemoryOutlined {...{ sx: { fontSize: 20, zIndex: 10, color: "text.primary" } }} />
                    <AddOutlined
                        {...{
                            sx: {
                                fontSize: 13,
                                position: "absolute",
                                top: -4,
                                right: -4,
                                zIndex: 20,
                                color: "white",
                                backgroundColor: "info.main",
                                borderRadius: "50%",
                            },
                        }}
                    />
                </Box>
            </Tooltip>
        );
    }

    return null;
};

export default FirmwareHardwareFilesIcon;
export { FirmwareHardwareFilesIconProps };
