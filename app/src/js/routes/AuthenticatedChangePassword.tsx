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
import { LockOutlined } from "@mui/icons-material";
import getFields from "~app/fields/ChangePasswordFields";
import Change from "~app/components/Crud/Change";
import { useUser } from "~app/contexts/User";

const AuthenticatedChangePassword = () => {
    const { navigateHomepage } = useUser();

    const fields = getFields();

    return (
        <Change
            {...{
                endpoint: "/authenticated/change/password",
                fields,
                snackbarLabel: "changePassword.snackbar.success",
                titleProps: {
                    title: "route.title.profile",
                    subtitle: "route.subtitle.changePassword",
                    icon: <LockOutlined />,
                },
                onSubmitSuccess(defaultOnSubmitSuccess) {
                    defaultOnSubmitSuccess();
                    navigateHomepage();
                },
            }}
        />
    );
};

export default AuthenticatedChangePassword;
