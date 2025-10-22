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
import { ButtonLink, ButtonLinkProps, ColumnInterface, Optional } from "@arteneo/forge";
import { PasswordOutlined } from "@mui/icons-material";

type ResultChangePasswordProps = Optional<ButtonLinkProps, "to"> & ColumnInterface;

const ResultChangePassword = ({ result, ...props }: ResultChangePasswordProps) => {
    if (typeof result === "undefined") {
        throw new Error("ResultEdit component: Missing required result prop");
    }

    return (
        <ButtonLink
            {...{
                label: "action.changePassword",
                to: "../changepassword/" + result.id,
                color: "warning",
                variant: "contained",
                size: "small",
                startIcon: <PasswordOutlined />,
                deny: result.deny,
                denyKey: "changePassword",
                ...props,
            }}
        />
    );
};

export default ResultChangePassword;
export { ResultChangePasswordProps };
