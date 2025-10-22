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
import { TranslateVariablesInterface } from "@arteneo/forge";
import { Box, Paper, Typography } from "@mui/material";
import { Link, To } from "react-router-dom";
import { useTranslation } from "react-i18next";

interface TileProps {
    title: string;
    titleVariables?: TranslateVariablesInterface;
    disableTranslate?: boolean;
    to: To;
    icon: React.ReactNode;
}

const Tile = ({ title, titleVariables = {}, disableTranslate = false, to, icon }: TileProps) => {
    const { t } = useTranslation();

    return (
        <Paper
            {...{
                component: Link,
                to,
                sx: {
                    p: { xs: 2, sm: 3 },
                    transition: "border-color 0.2s ease-in",
                    "&:hover": {
                        textDecoration: "none",
                        borderColor: "primary.main",
                    },
                },
            }}
        >
            <Box {...{ sx: { display: "flex", flexDirection: "column", alignItems: "center" } }}>
                <Box
                    {...{
                        sx: {
                            display: "flex",
                            alignItems: "center",
                            mb: 1.5,
                            "& > svg": { fontSize: "2.5rem" },
                        },
                    }}
                >
                    {icon}
                </Box>
                <Typography {...{ component: "h2", variant: "h1", align: "center" }}>
                    {!disableTranslate ? t(title, titleVariables) : title}
                </Typography>
            </Box>
        </Paper>
    );
};

export default Tile;
export { TileProps };
