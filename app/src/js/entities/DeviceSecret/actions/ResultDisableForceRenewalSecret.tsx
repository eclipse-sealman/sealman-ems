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
import { SyncDisabledOutlined } from "@mui/icons-material";
import { useDetails } from "~app/contexts/Details";

type ResultDisableForceRenewalSecretProps = Optional<ResultButtonDialogAlertConfirmProps, "dialogProps">;

const ResultDisableForceRenewalSecret = ({ result, ...props }: ResultDisableForceRenewalSecretProps) => {
    const { reload } = useDetails();
    if (typeof result === "undefined") {
        throw new Error("ResultDisableForceRenewalSecret component: Missing required result prop");
    }

    return (
        <ResultButtonDialogAlertConfirm
            {...{
                result,
                size: "small",
                startIcon: <SyncDisabledOutlined />,
                label: "action.disableForceRenewal",
                color: "info",
                variant: "contained",
                denyKey: "disableForceRenewal",
                deny: result.deny,
                denyBehavior: "hide",
                dialogProps: (result) => ({
                    title: "resultDisableForceRenewalSecret.dialog.title",
                    label: "resultDisableForceRenewalSecret.dialog.label",
                    labelVariables: { deviceSecretRepresentation: result.representation },
                    confirmProps: {
                        endIcon: <SyncDisabledOutlined />,
                        label: "action.disableForceRenewal",
                        color: "info",
                        variant: "contained",
                        endpoint: "/devicesecret/" + result.id + "/disable/force/renewal",
                        snackbarLabel: "resultDisableForceRenewalSecret.snackbar.success",
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

export default ResultDisableForceRenewalSecret;
export { ResultDisableForceRenewalSecretProps };
