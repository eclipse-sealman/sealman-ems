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
import { Box, SxProps, Typography } from "@mui/material";
import { useTranslation } from "react-i18next";
import { Link, To } from "react-router-dom";
import ButtonBack from "~app/components/Common/ButtonBack";

interface SurfaceTitleProps {
    title?: string;
    titleVariables?: TranslateVariablesInterface;
    titleTo?: To;
    disableTitleTranslate?: boolean;
    subtitle?: string;
    subtitleVariables?: TranslateVariablesInterface;
    subtitleTo?: To;
    disableSubtitleTranslate?: boolean;
    hint?: string;
    hintVariables?: TranslateVariablesInterface;
    hintTo?: To;
    disableHintTranslate?: boolean;
    icon?: React.ReactNode;
    disableBackButton?: boolean;
}

const SurfaceTitle = ({
    title,
    titleVariables = {},
    titleTo,
    disableTitleTranslate = false,
    subtitle,
    subtitleVariables = {},
    subtitleTo,
    disableSubtitleTranslate = false,
    hint,
    hintVariables = {},
    hintTo,
    disableHintTranslate = false,
    icon,
    disableBackButton = false,
}: SurfaceTitleProps) => {
    const { t } = useTranslation();

    const getLinkProps = (to?: To) => {
        if (typeof to === "undefined") {
            return {};
        }

        return {
            to,
            component: Link,
        };
    };

    const getTranslate = (
        label: string,
        labelVariables: TranslateVariablesInterface = {},
        disableTranslate = false
    ) => {
        if (disableTranslate) {
            return label;
        }

        return t(label, labelVariables);
    };

    const sxOverflow: SxProps = {
        maxWidth: "460px",
        textOverflow: "ellipsis",
        overflow: "hidden",
        whiteSpace: "nowrap",
    };

    return (
        <Box {...{ sx: { display: "flex", alignItems: "center", gap: 1, mb: { xs: 1, sm: 1.5 } } }}>
            <Box {...{ sx: { display: "flex", alignItems: "center", color: "primary.main" } }}>{icon}</Box>
            {title && (
                <Typography {...{ variant: "h1", sx: sxOverflow, ...getLinkProps(titleTo) }}>
                    {getTranslate(title, titleVariables, disableTitleTranslate)}
                </Typography>
            )}
            {subtitle && (
                <>
                    <Box {...{ sx: { fontSize: 20, fontWeight: 600, color: "grey.400", lineHeight: 1.25 } }}>&gt;</Box>
                    <Typography {...{ variant: "h2", sx: sxOverflow, ...getLinkProps(subtitleTo) }}>
                        {getTranslate(subtitle, subtitleVariables, disableSubtitleTranslate)}
                    </Typography>
                </>
            )}
            {hint && (
                <>
                    <Box {...{ sx: { fontSize: 18, fontWeight: 600, color: "grey.400", lineHeight: 1.25 } }}>&gt;</Box>
                    <Typography {...{ variant: "h3", sx: sxOverflow, ...getLinkProps(hintTo) }}>
                        {" "}
                        {getTranslate(hint, hintVariables, disableHintTranslate)}
                    </Typography>
                </>
            )}
            {!disableBackButton && <ButtonBack {...{ sx: { ml: 2 } }} />}
        </Box>
    );
};

export default SurfaceTitle;
export { SurfaceTitleProps };
