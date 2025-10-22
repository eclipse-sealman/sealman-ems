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
import { Form, useHandleCatch, useLoader } from "@arteneo/forge";
import axios, { AxiosError, AxiosResponse } from "axios";
import { FormikHelpers, FormikValues } from "formik";
import { useTranslation } from "react-i18next";
import getFields from "~app/fields/LoginFields";
import AuthenticationFieldset from "~app/fieldsets/AuthenticationFieldset";
import { useUser } from "~app/contexts/User";
import LayoutAuthentication from "~app/components/Layout/LayoutAuthentication";
import ButtonLoginMicrosoftOidc from "~app/components/Login/ButtonLoginMicrosoftOidc";

const Login = () => {
    const { t } = useTranslation();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();
    const { login } = useUser();
    const fields = getFields();

    return (
        <LayoutAuthentication {...{ title: "login.title", subtitle: "login.subtitle" }}>
            <Form
                {...{
                    fields,
                    children: <AuthenticationFieldset {...{ fields, label: "login.action" }} />,
                    onSubmit: (values: FormikValues, helpers: FormikHelpers<FormikValues>) => {
                        showLoader();

                        // Different instance needs to be used to skip existing interceptors
                        const axiosInstance = axios.create();
                        axiosInstance
                            .request({
                                method: "post",
                                url: "/authentication/login_check",
                                data: values,
                            })
                            .then((response: AxiosResponse) => {
                                helpers.setSubmitting(false);
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

                                if (error?.response?.status === 401) {
                                    const message = error?.response?.data?.message;
                                    let errorMessage = "validation.authentication.invalidCredentials";

                                    switch (message) {
                                        case "accountDisabled":
                                            errorMessage = "validation.authentication.accountDisabled";
                                            break;
                                        case "accountDisabledTooManyFailedLoginAttempts":
                                            errorMessage =
                                                "validation.authentication.accountDisabledTooManyFailedLoginAttempts";
                                            break;
                                        case "accessDeniedNoVpnSecuritySuite":
                                            errorMessage = "validation.authentication.accessDeniedNoVpnSecuritySuite";
                                            break;
                                    }

                                    helpers.setFieldError("username", t(errorMessage));
                                    helpers.setSubmitting(false);
                                    return;
                                }

                                handleCatch(error, helpers);
                            });
                    },
                }}
            />
            <ButtonLoginMicrosoftOidc />
        </LayoutAuthentication>
    );
};

export default Login;
