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
import { CheckOutlined } from "@mui/icons-material";

interface CrudFormViewOnlySubmitProps {
    submitButtonProps?: Partial<ButtonProps>;
    children: React.ReactNode;
}

const CrudFormViewOnlySubmit = ({ submitButtonProps, children }: CrudFormViewOnlySubmitProps) => {
    const { isSubmitting } = useFormikContext();

    return (
        <>
            <Box {...{ mb: { xs: 2, md: 4 } }}>{children}</Box>
            <Box {...{ sx: { display: "flex", justifyContent: "flex-end", flexWrap: "wrap-reverse", gap: 2 } }}>
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

export default CrudFormViewOnlySubmit;
export { CrudFormViewOnlySubmitProps };
