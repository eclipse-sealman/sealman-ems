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
import { Box, Chip } from "@mui/material";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { TranslateVariablesInterface } from "@arteneo/forge";

interface HeaderVpnConnectionDisplayProps {
    title: string;
    titleVariables?: TranslateVariablesInterface;
    to?: string;
    buttonLabel?: string;
    buttonLabelVariables?: TranslateVariablesInterface;
    children?: React.ReactNode;
}

const HeaderVpnConnectionDisplay = ({
    title,
    titleVariables = {},
    to,
    buttonLabel = "header.connection.howToConnect",
    buttonLabelVariables = {},
    children,
}: HeaderVpnConnectionDisplayProps) => {
    const { t } = useTranslation();

    return (
        <Box {...{ sx: { display: "flex", flexDirection: "row", alignItems: "center" } }}>
            <Box {...{ sx: { color: "text.secondary", fontSize: "0.95rem" } }}>
                {t(title, titleVariables)}
                {children}
            </Box>
            {to && (
                <Link to={to}>
                    <Chip
                        {...{
                            label: t(buttonLabel, buttonLabelVariables),
                            variant: "outlined",
                            sx: {
                                ml: 2,
                                cursor: "pointer",
                                borderRadius: "12px",
                                borderColor: "#dadada",
                                fontSize: "0.9rem",
                            },
                        }}
                    />
                </Link>
            )}
        </Box>
    );
};

export default HeaderVpnConnectionDisplay;
