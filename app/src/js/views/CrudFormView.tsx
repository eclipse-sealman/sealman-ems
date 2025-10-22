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
import { useFormikContext } from "formik";
import { Button, ButtonProps } from "@arteneo/forge";
import { Box } from "@mui/material";
import { ArrowBackIosNewOutlined, CheckOutlined } from "@mui/icons-material";
import { useNavigate } from "react-router-dom";

interface CrudFormViewProps {
    backButtonProps?: Partial<ButtonProps>;
    submitButtonProps?: Partial<ButtonProps>;
    children: React.ReactNode;
}

const CrudFormView = ({ backButtonProps, submitButtonProps, children }: CrudFormViewProps) => {
    const navigate = useNavigate();
    const { isSubmitting } = useFormikContext();

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
                <Button
                    {...{
                        loading: isSubmitting,
                        label: "action.submit",
                        variant: "contained",
                        color: "success",
                        type: "submit",
                        endIcon: <CheckOutlined />,
                        ...submitButtonProps,
                    }}
                />
            </Box>
        </>
    );
};

export default CrudFormView;
export { CrudFormViewProps };
