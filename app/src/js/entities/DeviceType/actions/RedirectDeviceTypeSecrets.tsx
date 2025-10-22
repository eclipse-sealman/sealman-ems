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
import { ButtonLink, ResultRedirectTableQueryProps } from "@arteneo/forge";
import { VpnKeyOutlined } from "@mui/icons-material";

type RedirectDeviceTypeSecretsProps = Omit<ResultRedirectTableQueryProps, "to">;

const RedirectDeviceTypeSecrets = ({ result, ...props }: RedirectDeviceTypeSecretsProps) => {
    if (typeof result === "undefined") {
        throw new Error("RedirectDeviceTypeSecrets component: Missing required result prop");
    }

    return (
        <ButtonLink
            {...{
                label: "action.deviceTypeSecrets",
                color: "info",
                size: "small",
                variant: "contained",
                to: "/configuration/devicetypesecret/" + result.id + "/list",
                startIcon: <VpnKeyOutlined />,
                ...props,
            }}
        />
    );
};

export default RedirectDeviceTypeSecrets;
export { RedirectDeviceTypeSecretsProps };
