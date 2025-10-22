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
import { Button, ButtonProps } from "@arteneo/forge";
import { Box } from "@mui/material";
import { ArrowBackIosNewOutlined } from "@mui/icons-material";
import { useNavigate } from "react-router-dom";

interface CrudFormDetailsViewProps {
    backButtonProps?: Partial<ButtonProps>;
    children: React.ReactNode;
}

const CrudFormDetailsView = ({ backButtonProps, children }: CrudFormDetailsViewProps) => {
    const navigate = useNavigate();

    return (
        <>
            <Box {...{ mb: { xs: 2, md: 4 } }}>{children}</Box>
            <Box {...{ sx: { display: "flex", justifyContent: "space-between", flexWrap: "wrap-reverse", gap: 2 } }}>
                <Button
                    {...{
                        label: "action.back",
                        variant: "contained",
                        color: "info",
                        startIcon: <ArrowBackIosNewOutlined />,
                        onClick: () => navigate(-1),
                        ...backButtonProps,
                    }}
                />
            </Box>
        </>
    );
};

export default CrudFormDetailsView;
export { CrudFormDetailsViewProps };
