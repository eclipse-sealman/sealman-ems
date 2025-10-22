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
import { TranslateVariablesInterface } from "@arteneo/forge";
import GlobalLoader from "~app/components/Layout/GlobalLoader";
import Footer from "~app/components/Layout/Footer";
import logo from "~images/logo.svg";
import backgroundLoginImage from "~images/background-login-image.jpg";

interface LayoutAuthenticationProps {
    title: string;
    titleVariables?: TranslateVariablesInterface;
    subtitle: string;
    subtitleVariables?: TranslateVariablesInterface;
    children: React.ReactNode;
}

const LayoutAuthentication = ({
    title,
    titleVariables = {},
    subtitle,
    subtitleVariables = {},
    children,
}: LayoutAuthenticationProps) => {
    const { t } = useTranslation();

    return (
        <Box
            {...{
                sx: {
                    minHeight: "100vh",
                    position: "relative",
                    backgroundImage: "url(" + backgroundLoginImage + ")",
                    backgroundSize: "cover",
                    backgroundPosition: "center",
                    pt: 2,
                    pb: 2,
                    display: "flex",
                    alignItems: "center",
                },
            }}
        >
            <GlobalLoader />
            {/* minHeight: 75vh makes content vertically aligned at around ~1/3 from top */}
            <Container {...{ maxWidth: "md", sx: { minHeight: "75vh", maxWidth: { md: "780px" } } }}>
                <Box {...{ sx: { borderRadius: 1, backgroundColor: "background.default", overflow: "hidden" } }}>
                    <Box
                        {...{
                            sx: {
                                backgroundColor: "white",
                                py: 1,
                                display: "flex",
                                justifyContent: "center",
                                alignItems: "center",
                                minHeight: {
                                    xs: 90,
                                    sm: 110,
                                },
                            },
                        }}
                    >
                        <Box
                            {...{
                                component: "img",
                                sx: { maxHeight: "155px", height: "100%", objectFit: "contain" },
                                src: logo,
                                alt: t("alt.logo"),
                            }}
                        />
                    </Box>
                    <Box
                        {...{
                            sx: {
                                py: { xs: 2, sm: 6 },
                                px: { xs: 3, sm: 4 },
                                display: "flex",
                                flexDirection: "column",
                                margin: "auto",
                                maxWidth: "550px",
                            },
                        }}
                    >
                        <Box {...{ sx: { mb: { xs: 2, sm: 3 } } }}>
                            <Typography {...{ variant: "h1", sx: { fontSize: 30, mb: 1 } }}>
                                {t(title, titleVariables)}
                            </Typography>
                            <Typography {...{ component: "h2", variant: "h4", sx: { mb: 1 } }}>
                                {t(subtitle, subtitleVariables)}
                            </Typography>
                        </Box>
                        {children}
                    </Box>
                    <Box
                        {...{
                            sx: {
                                mx: 5,
                                borderTopWidth: "1px",
                                borderTopStyle: "solid",
                                borderTopColor: "grey.300",
                                py: 2,
                                display: "flex",
                                justifyContent: "center",
                                alignItems: "center",
                            },
                        }}
                    >
                        <Footer />
                    </Box>
                </Box>
            </Container>
        </Box>
    );
};

export default LayoutAuthentication;
export { LayoutAuthenticationProps };
