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
import { SyncProblemOutlined } from "@mui/icons-material";
import { useDetails } from "~app/contexts/Details";

type ResultEnableForceRenewalSecretProps = Optional<ResultButtonDialogAlertConfirmProps, "dialogProps">;

const ResultEnableForceRenewalSecret = ({ result, ...props }: ResultEnableForceRenewalSecretProps) => {
    const { reload } = useDetails();
    if (typeof result === "undefined") {
        throw new Error("ResultEnableForceRenewalSecret component: Missing required result prop");
    }

    return (
        <ResultButtonDialogAlertConfirm
            {...{
                result,
                size: "small",
                startIcon: <SyncProblemOutlined />,
                label: "action.enableForceRenewal",
                color: "info",
                variant: "contained",
                denyKey: "enableForceRenewal",
                deny: result.deny,
                denyBehavior: "hide",
                dialogProps: (result) => ({
                    title: "resultEnableForceRenewalSecret.dialog.title",
                    label: "resultEnableForceRenewalSecret.dialog.label",
                    labelVariables: { deviceSecretRepresentation: result.representation },
                    confirmProps: {
                        endIcon: <SyncProblemOutlined />,
                        label: "action.enableForceRenewal",
                        color: "info",
                        variant: "contained",
                        endpoint: "/devicesecret/" + result.id + "/enable/force/renewal",
                        snackbarLabel: "resultEnableForceRenewalSecret.snackbar.success",
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

export default ResultEnableForceRenewalSecret;
export { ResultEnableForceRenewalSecretProps };
