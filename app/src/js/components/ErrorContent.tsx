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
import { Box, Container, Typography } from "@mui/material";
import { useTranslation } from "react-i18next";
import Surface from "~app/components/Common/Surface";

interface ErrorContentProps {
    error: number;
    children?: React.ReactNode;
}

const ErrorContent = ({ error, children }: ErrorContentProps) => {
    const { t } = useTranslation();

    const errorLabel = [401, 403, 404, 500].includes(error) ? error : 500;

    return (
        <Container maxWidth="sm">
            <Box {...{ mt: { xs: 2, md: 8 } }}>
                <Surface>
                    <Box p={{ xs: 3, sm: 4 }}>
                        <Typography {...{ mb: 2, variant: "h2", align: "center" }}>
                            {t("error.title." + errorLabel)}
                        </Typography>

                        <Typography {...{ mb: 2, variant: "h1", align: "center", sx: { fontSize: 70 } }}>
                            {errorLabel}
                        </Typography>

                        <Typography {...{ mb: 4, align: "center" }}>{t("error.body." + errorLabel)}</Typography>

                        {children}
                    </Box>
                </Surface>
            </Box>
        </Container>
    );
};

export default ErrorContent;
export { ErrorContentProps };
