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
import { Button } from "@arteneo/forge";
import { Box } from "@mui/material";
import { useConfiguration } from "~app/contexts/Configuration";

const ButtonLoginMicrosoftOidc = () => {
    const { singleSignOn } = useConfiguration();

    if (singleSignOn !== "microsoftOidc") {
        return null;
    }

    return (
        <Box {...{ sx: { display: "flex", justifyContent: "center", mt: 4 } }}>
            <Button
                {...{
                    component: "a",
                    href: "/web/api/authentication/sso/microsoftoidc/redirect",
                    label: "login.microsoftOidc.action",
                    variant: "outlined",
                    color: "primary",
                    sx: {
                        textTransform: "uppercase",
                        fontWeight: 600,
                        minWidth: 150,
                    },
                }}
            />
        </Box>
    );
};

export default ButtonLoginMicrosoftOidc;
