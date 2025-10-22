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
import { CloseOutlined } from "@mui/icons-material";
import { ResultButtonDialogAlertConfirm, ResultButtonDialogAlertConfirmProps, Optional } from "@arteneo/forge";
import { useDetails } from "~app/contexts/Details";

type DetachProductionProps = Optional<ResultButtonDialogAlertConfirmProps, "dialogProps">;

const DetachProduction = ({ result, path, dialogProps, ...props }: DetachProductionProps) => {
    const { reload } = useDetails();

    if (typeof result === "undefined") {
        throw new Error("DetachProduction component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;

    const internalDialogProps: ReturnType<ResultButtonDialogAlertConfirmProps["dialogProps"]> = {
        title: "templateVersion.dialog.detachProduction.title",
        label: "templateVersion.dialog.detachProduction.label",
        labelVariables: {
            representation: value.representation,
        },
        alertProps: {
            severity: "error",
        },
        confirmProps: {
            label: "templateVersion.action.detachProduction",
            color: "error",
            variant: "contained",
            endIcon: <CloseOutlined />,
            endpoint: "/templateversion/detach/production/" + value.id,
            onSuccess: (defaultOnSuccess) => {
                defaultOnSuccess();
                reload();
            },
        },
    };

    return (
        <ResultButtonDialogAlertConfirm
            {...{
                result,
                label: "templateVersion.action.detachProduction",
                denyKey: "detachProduction",
                denyBehavior: "hide",
                color: "error",
                size: "small",
                variant: "contained",
                startIcon: <CloseOutlined />,
                dialogProps: () =>
                    _.merge(internalDialogProps, typeof dialogProps !== "undefined" ? dialogProps(value) : {}),
                ...props,
            }}
        />
    );
};

export default DetachProduction;
export { DetachProductionProps };
