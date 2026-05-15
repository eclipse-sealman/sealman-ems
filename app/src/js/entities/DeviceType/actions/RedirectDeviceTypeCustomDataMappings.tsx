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
import { DataObjectOutlined } from "@mui/icons-material";

type RedirectDeviceTypeCustomDataMappingsProps = Omit<ResultRedirectTableQueryProps, "to">;

const RedirectDeviceTypeCustomDataMappings = ({ result, ...props }: RedirectDeviceTypeCustomDataMappingsProps) => {
    if (typeof result === "undefined") {
        throw new Error("RedirectDeviceTypeCustomDataMappings component: Missing required result prop");
    }

    return (
        <ButtonLink
            {...{
                label: "action.deviceTypeCustomDataMappings",
                color: "info",
                size: "small",
                variant: "contained",
                to: "/configuration/devicetypecustomdatamappings/" + result.id + "/list",
                startIcon: <DataObjectOutlined />,
                denyBehavior: "hide",
                denyKey: "hasCustomData",
                deny: result.deny,
                ...props,
            }}
        />
    );
};

export default RedirectDeviceTypeCustomDataMappings;
export { RedirectDeviceTypeCustomDataMappingsProps };
