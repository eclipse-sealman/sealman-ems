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
import { ResultButtonLink, ResultButtonLinkProps, ColumnActionInterface, Optional } from "@arteneo/forge";
import { EditOutlined } from "@mui/icons-material";

type ResultCreateSecretProps = Optional<ResultButtonLinkProps, "to"> & ColumnActionInterface;

const ResultCreateSecret = ({ result, ...props }: ResultCreateSecretProps) => {
    if (typeof result === "undefined") {
        throw new Error("ResultCreateSecret component: Missing required result prop");
    }

    return (
        <ResultButtonLink
            {...{
                label: "action.edit",
                color: "info",
                size: "small",
                variant: "contained",
                startIcon: <EditOutlined />,
                to: "/devicesecret/create/" + result?.device?.id + "/" + result?.deviceTypeSecret?.id,
                denyKey: "create",
                denyBehavior: "hide",
                result,
                ...props,
            }}
        />
    );
};

export default ResultCreateSecret;
export { ResultCreateSecretProps };
