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
import { Box } from "@mui/material";
import { KeyboardArrowLeftOutlined } from "@mui/icons-material";
import { Button, useHandleCatch, useLoader } from "@arteneo/forge";
import axios, { AxiosError, AxiosResponse } from "axios";
import { useSearchParams } from "react-router-dom";
import { useUser } from "~app/contexts/User";
import LayoutAuthentication from "~app/components/Layout/LayoutAuthentication";
import CircularLoader from "~app/components/Layout/CircularLoader";

const SsoMicrosoftOidcLogin = () => {
    const [searchParams] = useSearchParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();
    const { login } = useUser();

    React.useEffect(() => ssoLogin(), []);

    const ssoLogin = () => {
        const code = searchParams.get("code");
        const state = searchParams.get("state");

        showLoader();

        // Different instance needs to be used to skip existing interceptors
        const axiosInstance = axios.create();
        axiosInstance
            .get("/authentication/sso/microsoftoidc/authorize/" + code + "/" + state)
            .then((response: AxiosResponse) => {
                hideLoader();

                const {
                    username,
                    representation,
                    lastLoginAt,
                    roles,
                    totpSecret,
                    totpUrl,
                    token: accessToken,
                    refreshToken,
                    refreshTokenExpiration,
                    sessionTimeout,
                    accessTokenTtl,
                } = response.data;

                login({
                    username,
                    representation,
                    lastLoginAt,
                    roles,
                    totpSecret,
                    totpUrl,
                    accessToken,
                    refreshToken,
                    refreshTokenExpiration,
                    sessionTimeout,
                    accessTokenTtl,
                });
            })
            // eslint-disable-next-line
            .catch((error: AxiosError<any>) => {
                hideLoader();
                handleCatch(error);
            });
    };

    return (
        <LayoutAuthentication {...{ title: "login.microsoftOidc.title", subtitle: "login.microsoftOidc.subtitle" }}>
            <Box sx={{ display: "flex", gap: 4, flexDirection: "column", alignItems: "flex-start" }}>
                <CircularLoader />
                <Button
                    {...{
                        // Use browser redirect to reload the page
                        component: "a",
                        href: "/authentication/login",
                        startIcon: <KeyboardArrowLeftOutlined />,
                        label: "action.back",
                        variant: "contained",
                        color: "primary",
                    }}
                />
            </Box>
        </LayoutAuthentication>
    );
};

export default SsoMicrosoftOidcLogin;
