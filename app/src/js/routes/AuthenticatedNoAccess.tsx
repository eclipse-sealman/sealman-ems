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
import { useTranslation } from "react-i18next";
import { Alert, Box } from "@mui/material";
import { Button } from "@arteneo/forge";
import { useUser } from "~app/contexts/User";
import LayoutAuthentication from "~app/components/Layout/LayoutAuthentication";
import { useConfiguration } from "~app/contexts/Configuration";

const AuthenticatedNoAccess = () => {
    const { t } = useTranslation();
    const { logout } = useUser();
    const { reload, maintenanceMode } = useConfiguration();

    return (
        <LayoutAuthentication {...{ title: "authenticatedNoAccess.title", subtitle: "authenticatedNoAccess.subtitle" }}>
            <>
                <Alert {...{ severity: "warning" }}>
                    {t("authenticatedNoAccess." + (maintenanceMode ? "maintenanceModeEnabled" : "noAccess"))}
                </Alert>
                <Box {...{ sx: { display: "flex", justifyContent: "center", mt: 4 } }}>
                    <Button
                        {...{
                            label: "authenticatedNoAccess.action",
                            onClick: () => {
                                logout(() => reload());
                            },
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
            </>
        </LayoutAuthentication>
    );
};

export default AuthenticatedNoAccess;
