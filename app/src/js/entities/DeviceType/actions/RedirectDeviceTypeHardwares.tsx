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
import { MemoryOutlined } from "@mui/icons-material";

type RedirectDeviceTypeHardwaresProps = Omit<ResultRedirectTableQueryProps, "to">;

const RedirectDeviceTypeHardwares = ({ result, ...props }: RedirectDeviceTypeHardwaresProps) => {
    if (typeof result === "undefined") {
        throw new Error("RedirectDeviceTypeHardwares component: Missing required result prop");
    }

    return (
        <ButtonLink
            {...{
                label: "action.deviceTypeHardwares",
                color: "info",
                size: "small",
                variant: "contained",
                to: "/configuration/devicetypehardware/" + result.id + "/list",
                startIcon: <MemoryOutlined />,
                denyBehavior: "hide",
                denyKey: "hasHardwares",
                deny: result.deny,
                ...props,
            }}
        />
    );
};

export default RedirectDeviceTypeHardwares;
export { RedirectDeviceTypeHardwaresProps };
