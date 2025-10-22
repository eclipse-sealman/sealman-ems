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
import { ResultButtonDialogAlertConfirm, ResultButtonDialogAlertConfirmProps, Optional } from "@arteneo/forge";

type ResultResetLoginAttemptsProps = Optional<ResultButtonDialogAlertConfirmProps, "dialogProps">;

const ResultResetLoginAttempts = ({ result, path, dialogProps, ...props }: ResultResetLoginAttemptsProps) => {
    if (typeof result === "undefined") {
        throw new Error("ResultResetLoginAttempts component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;

    const internalDialogProps: ReturnType<ResultButtonDialogAlertConfirmProps["dialogProps"]> = {
        title: "resetLoginAttempts.dialog.title",
        label: "resetLoginAttempts.dialog.label",
        labelVariables: {
            representation: value.representation,
        },
        alertProps: {
            severity: "error",
        },
        confirmProps: {
            label: "resetLoginAttempts.action",
            color: "error",
            variant: "contained",
            endIcon: <AppBlockingOutlined />,
            endpoint: "/user/resetloginattempts/" + value.id,
            snackbarLabel: "resetLoginAttempts.snackbar.success",
        },
    };

    return (
        <ResultButtonDialogAlertConfirm
            {...{
                result,
                label: "resetLoginAttempts.action",
                denyKey: "resetLoginAttempts",
                denyBehavior: "hide",
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

export default ResultResetLoginAttempts;
export { ResultResetLoginAttemptsProps };
