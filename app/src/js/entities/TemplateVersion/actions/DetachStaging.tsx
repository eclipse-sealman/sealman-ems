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

type DetachStagingProps = Optional<ResultButtonDialogAlertConfirmProps, "dialogProps">;

const DetachStaging = ({ result, path, dialogProps, ...props }: DetachStagingProps) => {
    const { reload } = useDetails();

    if (typeof result === "undefined") {
        throw new Error("DetachStaging component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;

    const internalDialogProps: ReturnType<ResultButtonDialogAlertConfirmProps["dialogProps"]> = {
        title: "templateVersion.dialog.detachStaging.title",
        label: "templateVersion.dialog.detachStaging.label",
        labelVariables: {
            representation: value.representation,
        },
        alertProps: {
            severity: "error",
        },
        confirmProps: {
            label: "templateVersion.action.detachStaging",
            color: "error",
            variant: "contained",
            endIcon: <CloseOutlined />,
            endpoint: "/templateversion/detach/staging/" + value.id,
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
                label: "templateVersion.action.detachStaging",
                denyKey: "detachStaging",
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

export default DetachStaging;
export { DetachStagingProps };
