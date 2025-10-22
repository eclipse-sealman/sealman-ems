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
import { Button, useError } from "@arteneo/forge";
import { Box } from "@mui/material";
import { useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import ErrorContent from "~app/components/ErrorContent";
import { useUser } from "~app/contexts/User";

const Error = () => {
    const { t } = useTranslation();
    const { logout: logoutUser } = useUser();
    const navigate = useNavigate();
    const { error, clearErrors } = useError();

    if (typeof error === "undefined") {
        return null;
    }

    const errorLabel = [401, 403, 404, 500].includes(error) ? error : 500;

    const logout = () => {
        // We need to call clearErrors() after logout is done to avoid showing and loading the screen that caused the error (i.e. user list)
        logoutUser(() => clearErrors());
    };

    return (
        <ErrorContent {...{ error }}>
            <Box {...{ sx: { display: "flex", gap: 2 } }}>
                {error !== 404 && (
                    <Button
                        {...{
                            variant: "contained",
                            size: "large",
                            color: "warning",
                            fullWidth: true,
                            onClick: () => logout(),
                        }}
                    >
                        {t("error.action." + (errorLabel === 401 ? "login" : "logout"))}
                    </Button>
                )}
                <Button
                    {...{
                        variant: "contained",
                        size: "large",
                        color: "info",
                        fullWidth: true,
                        onClick: () => {
                            if (error === 401) {
                                // In case of 401 we always need to clear any saved credentials. logout() will redirect to login
                                logout();
                                return;
                            }

                            clearErrors();
                            navigate("/");
                        },
                    }}
                >
                    {t("error.action.homepage")}
                </Button>
            </Box>
        </ErrorContent>
    );
};

export default Error;
