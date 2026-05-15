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
import { ResultButtonLink, ResultButtonLinkProps } from "@arteneo/forge";
import { MemoryOutlined } from "@mui/icons-material";

type RedirectFirmwareHardwareFilesProps = Omit<ResultButtonLinkProps, "to">;

const RedirectFirmwareHardwareFiles = ({ result, ...props }: RedirectFirmwareHardwareFilesProps) => {
    if (typeof result === "undefined") {
        throw new Error("RedirectFirmwareHardwareFiles component: Missing required result prop");
    }

    return (
        <ResultButtonLink
            {...{
                result,
                label: "action.firmwareHardwareFiles",
                color: "info",
                size: "small",
                variant: "contained",
                denyKey: "hardwareFilesList",
                denyBehavior: "hide",
                to: "/firmwarehardwarefile/" + result.id + "/list",
                startIcon: <MemoryOutlined />,
                ...props,
            }}
        />
    );
};

export default RedirectFirmwareHardwareFiles;
export { RedirectFirmwareHardwareFilesProps };
