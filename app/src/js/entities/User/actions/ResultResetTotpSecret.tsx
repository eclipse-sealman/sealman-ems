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
import { getIn } from "formik";
import _ from "lodash";
import { AppBlockingOutlined } from "@mui/icons-material";
import { DenyBehaviorType, Optional } from "@arteneo/forge";
import { useConfiguration } from "~app/contexts/Configuration";
import ResultButtonDialogAlertBoldConfirm, {
    ResultButtonDialogAlertBoldConfirmProps,
} from "~app/components/Table/actions/ResultButtonDialogAlertBoldConfirm";

type ResultResetTotpSecretProps = Optional<ResultButtonDialogAlertBoldConfirmProps, "dialogProps">;

const ResultResetTotpSecret = ({ result, path, dialogProps, ...props }: ResultResetTotpSecretProps) => {
    const { reload } = useConfiguration();

    if (typeof result === "undefined") {
        throw new Error("ResultResetTotpSecret component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;

    // Hide when TOTP is disabled system wide
    const denyBehavior: DenyBehaviorType =
        value?.deny?.resetTotpSecret === "deny.user.totpDisabled" ? "hide" : "disable";

    const internalDialogProps: ReturnType<ResultButtonDialogAlertBoldConfirmProps["dialogProps"]> = {
        title: "resetTotpSecret.dialog.title",
        label: "resetTotpSecret.dialog.label",
        labelVariables: {
            representation: value.representation,
        },
        boldLabel: "resetTotpSecret.dialog.boldLabel",
        alertProps: {
            severity: "error",
        },
        confirmProps: {
            label: "resetTotpSecret.action",
            color: "error",
            variant: "contained",
            endIcon: <AppBlockingOutlined />,
            endpoint: "/user/resettotpsecret/" + value.id,
            snackbarLabel: "resetTotpSecret.snackbar.success",
            onSuccess: (defaultOnSuccess) => {
                defaultOnSuccess();
                reload();
            },
        },
    };

    return (
        <ResultButtonDialogAlertBoldConfirm
            {...{
                result,
                label: "resetTotpSecret.action",
                denyKey: "resetTotpSecret",
                denyBehavior,
                color: "error",
                size: "small",
                variant: "contained",
                startIcon: <AppBlockingOutlined />,
                dialogProps: () =>
                    _.merge(internalDialogProps, typeof dialogProps !== "undefined" ? dialogProps(value) : {}),
                ...props,
            }}
        />
    );
};

export default ResultResetTotpSecret;
export { ResultResetTotpSecretProps };
