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
import { Box, Chip, ChipProps, Typography } from "@mui/material";
import { TranslateVariablesInterface } from "@arteneo/forge";
import { useTranslation } from "react-i18next";

interface DisplaySurfaceTitleProps {
    title: string;
    titleVariables?: TranslateVariablesInterface;
    chipLabel?: string;
    chipLabelVariables?: TranslateVariablesInterface;
    chipProps?: Omit<ChipProps, "label">;
}

const DisplaySurfaceTitle = ({
    title,
    titleVariables = {},
    chipLabel,
    chipLabelVariables = {},
    chipProps,
}: DisplaySurfaceTitleProps) => {
    const { t } = useTranslation();

    return (
        <Box {...{ sx: { display: "flex", alignItems: "center", gap: 2 } }}>
            <Typography {...{ variant: "h2", sx: { flexGrow: 1 } }}>{t(title, titleVariables)}</Typography>
            {typeof chipLabel !== "undefined" && (
                <Chip
                    {...{
                        label: t(chipLabel, chipLabelVariables),
                        size: "small",
                        variant: "outlined",
                        color: "info",
                        ...chipProps,
                    }}
                />
            )}
        </Box>
    );
};

export default DisplaySurfaceTitle;
export { DisplaySurfaceTitleProps };
