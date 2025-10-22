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
import { Box } from "@mui/material";
import { Button, ButtonProps } from "@arteneo/forge";

interface AuthenticationViewProps extends ButtonProps {
    children: React.ReactNode;
}

const AuthenticationView = ({
    children,
    variant = "contained",
    color = "primary",
    type = "submit",
    sx = {
        textTransform: "uppercase",
        fontWeight: 600,
        minWidth: 150,
    },
    ...buttonProps
}: AuthenticationViewProps) => {
    const { isSubmitting } = useFormikContext();

    return (
        <>
            {children}
            <Box {...{ sx: { display: "flex", justifyContent: "center", mt: 4 } }}>
                <Button
                    {...{
                        loading: isSubmitting,
                        variant,
                        color,
                        type,
                        sx,
                        ...buttonProps,
                    }}
                />
            </Box>
        </>
    );
};

export default AuthenticationView;
export { AuthenticationViewProps };
