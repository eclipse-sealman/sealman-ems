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
import { Alert, Box } from "@mui/material";
import { ArrowBackIosNewOutlined } from "@mui/icons-material";
import { useNavigate } from "react-router-dom";
import { Button, TranslateVariablesInterface } from "@arteneo/forge";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";
import { useTranslation } from "react-i18next";

interface SelectTilesProps<T> extends SurfaceTitleProps {
    tiles?: T[];
    renderTile: (tile: T) => React.ReactNode;
    emptyAlert?: string;
    emptyAlertVariables?: TranslateVariablesInterface;
    disableEmptyAlertTranslate?: boolean;
}

const SelectTiles = <T extends object>({
    tiles,
    renderTile,
    emptyAlert = "selectTiles.noResults",
    emptyAlertVariables = {},
    disableEmptyAlertTranslate,
    ...surfaceTitleProps
}: SelectTilesProps<T>) => {
    const { t } = useTranslation();
    const navigate = useNavigate();

    return (
        <>
            <Box {...{ mb: 1 }}>
                <SurfaceTitle {...surfaceTitleProps} />
            </Box>
            {typeof tiles !== "undefined" && (
                <>
                    {tiles.length > 0 ? (
                        <Box
                            {...{
                                sx: {
                                    display: "grid",
                                    gap: { xs: 2, lg: 4 },
                                    gridTemplateColumns: {
                                        xs: "minmax(0, 1fr)",
                                        sm: "repeat(2, minmax(0,1fr))",
                                        lg: "repeat(3, minmax(0,1fr))",
                                    },
                                },
                            }}
                        >
                            {tiles.map((tile) => renderTile(tile))}
                        </Box>
                    ) : (
                        <Alert severity="warning">
                            {disableEmptyAlertTranslate ? emptyAlert : t(emptyAlert, emptyAlertVariables)}
                        </Alert>
                    )}
                </>
            )}
            <Box
                {...{
                    sx: {
                        display: "flex",
                        gap: 2,
                        mt: { xs: 2, lg: 4 },
                    },
                }}
            >
                <Button
                    {...{
                        label: "action.back",
                        variant: "contained",
                        color: "info",
                        startIcon: <ArrowBackIosNewOutlined />,
                        onClick: () => navigate(-1),
                    }}
                />
            </Box>
        </>
    );
};

export default SelectTiles;
export { SelectTilesProps };
