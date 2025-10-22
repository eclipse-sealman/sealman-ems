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
import { Alert, Box, Typography } from "@mui/material";
import { useTranslation } from "react-i18next";
import QRCode from "react-qr-code";
import { Button } from "@arteneo/forge";
import { useUser } from "~app/contexts/User";
import LayoutAuthentication from "~app/components/Layout/LayoutAuthentication";
import Pre from "~app/components/Common/Pre";

const AuthenticationTotpSecret = () => {
    const { t } = useTranslation();
    const { logout, totpSecret, totpUrl } = useUser();

    if (typeof totpSecret === "undefined") {
        return null;
    }

    if (typeof totpUrl === "undefined") {
        return null;
    }

    return (
        <LayoutAuthentication {...{ title: "totpSecret.title", subtitle: "totpSecret.subtitle" }}>
            <Typography {...{ component: "h2", variant: "h4", sx: { mb: 4 } }}>
                {t("totpSecret.totpApplication")}
            </Typography>
            <Typography {...{ component: "h3", variant: "h2", sx: { mb: 2, textAlign: "center" } }}>
                {t("totpSecret.totpSecret")}
            </Typography>
            <Box {...{ sx: { display: "flex", justifyContent: "center", mb: 4 } }}>
                <Pre {...{ content: totpSecret }} />
            </Box>
            <Box {...{ sx: { display: "flex", justifyContent: "center", mb: 4 } }}>
                <Box {...{ sx: { display: "flex", p: 2, backgroundColor: "white", borderRadius: 1 } }}>
                    <QRCode
                        {...{
                            size: 256,
                            value: totpUrl,
                            viewBox: "0 0 256 256",
                        }}
                    />
                </Box>
            </Box>
            <Alert {...{ severity: "error", sx: { fontWeight: 600, mb: 4 } }}>{t("totpSecret.alert")}</Alert>
            <Box {...{ sx: { display: "flex", justifyContent: "center" } }}>
                <Button
                    {...{
                        onClick: () => logout(),
                        label: "totpSecret.action",
                        variant: "contained",
                        color: "primary",
                        type: "submit",
                        sx: {
                            textTransform: "uppercase",
                            fontWeight: 600,
                            minWidth: 150,
                        },
                    }}
                />
            </Box>
        </LayoutAuthentication>
    );
};

export default AuthenticationTotpSecret;
