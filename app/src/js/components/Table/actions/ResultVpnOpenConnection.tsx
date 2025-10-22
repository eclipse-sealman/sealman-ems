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
import {
    ResultButtonDialogAlertConfirm,
    ResultButtonDialogAlertConfirmProps,
    DenyBehaviorType,
    Optional,
} from "@arteneo/forge";
import { LinkOutlined } from "@mui/icons-material";
import EntityButtonInterface from "~app/definitions/EntityButtonInterface";
import { useDetails } from "~app/contexts/Details";

type ResultVpnOpenConnectionProps = Optional<ResultButtonDialogAlertConfirmProps, "dialogProps"> &
    EntityButtonInterface;

const ResultVpnOpenConnection = ({ result, entityPrefix, ...props }: ResultVpnOpenConnectionProps) => {
    const { reload } = useDetails();
    if (typeof result === "undefined") {
        throw new Error("ResultVpnOpenConnection component: Missing required result prop");
    }

    let denyBehavior: DenyBehaviorType = "disable";
    if (result?.deny?.vpnOpenConnection?.endsWith("alreadyConnected")) {
        denyBehavior = "hide";
    }

    return (
        <ResultButtonDialogAlertConfirm
            {...{
                result,
                size: "small",
                startIcon: <LinkOutlined />,
                denyKey: "vpnOpenConnection",
                denyBehavior: denyBehavior,
                label: "action.vpnOpenConnection",
                color: "info",
                variant: "contained",
                dialogProps: (result) => ({
                    title: "resultVpnOpenConnection.dialog.title",
                    label: "resultVpnOpenConnection.dialog.label",
                    labelVariables: { representation: result.representation },
                    confirmProps: {
                        endIcon: <LinkOutlined />,
                        label: "action.vpnOpenConnection",
                        color: "info",
                        variant: "contained",
                        endpoint: "/" + entityPrefix + "/" + result.id + "/open/vpnconnection",
                        snackbarLabel: "resultVpnOpenConnection.snackbar.success",
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

export default ResultVpnOpenConnection;
export { ResultVpnOpenConnectionProps };
