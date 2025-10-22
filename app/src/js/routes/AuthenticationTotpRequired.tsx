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
import { Form, useLoader } from "@arteneo/forge";
import getFields from "~app/fields/TotpRequiredFields";
import AuthenticationFieldset from "~app/fieldsets/AuthenticationFieldset";
import { useUser } from "~app/contexts/User";
import LayoutAuthentication from "~app/components/Layout/LayoutAuthentication";

const AuthenticationTotpRequired = () => {
    const { login } = useUser();
    const { hideLoader } = useLoader();

    const fields = getFields();

    return (
        <LayoutAuthentication {...{ title: "totpRequired.title", subtitle: "totpRequired.subtitle" }}>
            <Form
                {...{
                    fields,
                    children: <AuthenticationFieldset {...{ fields, label: "totpRequired.action" }} />,
                    endpoint: "/authentication/totp/required",
                    onSubmitSuccess: (defaultOnSubmitSuccess, values, helpers, response) => {
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
                    },
                }}
            />
        </LayoutAuthentication>
    );
};

export default AuthenticationTotpRequired;
