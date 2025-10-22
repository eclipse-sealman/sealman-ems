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
import { ResultButtonDialogAlertConfirm, ResultButtonDialogAlertConfirmProps, Optional } from "@arteneo/forge";
import { LinkOffOutlined } from "@mui/icons-material";
import { useDetails } from "~app/contexts/Details";

type ResultVpnCloseConnectionProps = Optional<ResultButtonDialogAlertConfirmProps, "dialogProps">;

const ResultVpnCloseConnection = ({ result, ...props }: ResultVpnCloseConnectionProps) => {
    const { reload } = useDetails();
    if (typeof result === "undefined") {
        throw new Error("ResultVpnCloseConnection component: Missing required result prop");
    }

    return (
        <ResultButtonDialogAlertConfirm
            {...{
                result,
                size: "small",
                startIcon: <LinkOffOutlined />,
                label: "action.vpnCloseConnection",
                color: "error",
                variant: "contained",
                dialogProps: (result) => ({
                    title: "resultVpnCloseConnection.dialog.title",
                    label: "resultVpnCloseConnection.dialog.label",
                    labelVariables: { user: result.user?.representation, target: result.target?.representation },
                    confirmProps: {
                        endIcon: <LinkOffOutlined />,
                        label: "action.vpnCloseConnection",
                        color: "error",
                        variant: "contained",
                        endpoint: "/vpnconnection/" + result.id + "/close/vpnconnection",
                        snackbarLabel: "resultVpnCloseConnection.snackbar.success",
                        onSuccess: (defaultOnSuccess) => {
                            defaultOnSuccess();
                            reload();
                        },
                    },
                }),
                ...props,
            }}
        />
    );
};

export default ResultVpnCloseConnection;
export { ResultVpnCloseConnectionProps };
