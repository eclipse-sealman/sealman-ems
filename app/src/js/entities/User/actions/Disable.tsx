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
import { PowerOffOutlined } from "@mui/icons-material";
import {
    Optional,
    ResultButtonDialogFormAlertFieldset,
    ResultButtonDialogFormAlertFieldsetProps,
} from "@arteneo/forge";
import { UserInterface } from "~app/entities/User/definitions";
import UserCertificateAutomaticBehaviorCollection from "~app/components/Form/fields/UserCertificateAutomaticBehaviorCollection";
import { changeSubmitValuesCertificateAutomaticBehaviorCollection } from "~app/components/Form/fields/CertificateAutomaticBehaviorCollection";

type DisableProps = Optional<ResultButtonDialogFormAlertFieldsetProps, "dialogProps">;

const Disable = ({ result, path, dialogProps, ...props }: DisableProps) => {
    if (typeof result === "undefined") {
        throw new Error("Disable component: Missing required result prop");
    }
    const user: UserInterface = path ? getIn(result, path) : result;

    const internalDialogProps: ReturnType<ResultButtonDialogFormAlertFieldsetProps["dialogProps"]> = {
        title: "userDisable.dialog.title",
        label: "userDisable.dialog.label",
        labelVariables: {
            representation: user.representation,
        },
        formProps: {
            endpoint: "/user/disable/" + result.id,
            initialValues: {
                ...user,
                enabled: false,
            },
            snackbarLabel: "userDisable.snackbar.success",
            fields: {
                certificateBehaviours: <UserCertificateAutomaticBehaviorCollection />,
            },
            changeSubmitValues: changeSubmitValuesCertificateAutomaticBehaviorCollection,
        },
    };

    return (
        <ResultButtonDialogFormAlertFieldset
            {...{
                result,
                label: "userDisable.action",
                denyKey: "disable",
                denyBehavior: "hide",
                color: "warning",
                size: "small",
                variant: "contained",
                startIcon: <PowerOffOutlined />,
                dialogProps: () =>
                    _.merge(internalDialogProps, typeof dialogProps !== "undefined" ? dialogProps(user) : {}),
                ...props,
            }}
        />
    );
};

export default Disable;
export { DisableProps };
