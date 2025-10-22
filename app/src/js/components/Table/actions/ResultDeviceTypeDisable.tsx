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
import _ from "lodash";
import { PowerSettingsNew } from "@mui/icons-material";
import { ResultInterface, Optional, useTable } from "@arteneo/forge";
import ButtonDialogAlertBoldConfirm, {
    ButtonDialogAlertBoldConfirmProps,
} from "~app/components/Common/ButtonDialogAlertBoldConfirm";

type ResultDeviceTypeDisableDialogProps = Optional<ButtonDialogAlertBoldConfirmProps["dialogProps"], "label">;

type InternalResultDeviceTypeDisableProps = Omit<ButtonDialogAlertBoldConfirmProps, "dialogProps">;

interface ResultDeviceTypeDisableProps extends InternalResultDeviceTypeDisableProps {
    result?: ResultInterface;
    dialogProps?: (result: ResultInterface) => ResultDeviceTypeDisableDialogProps;
}

const ResultDeviceTypeDisable = ({
    result,
    dialogProps,
    denyKey = "disable",
    ...props
}: ResultDeviceTypeDisableProps) => {
    const { reload } = useTable();

    if (typeof result === "undefined") {
        throw new Error("ResultDeviceTypeDisable component: Missing required result prop");
    }

    const internalDialogProps = {
        title: "resultDeviceTypeDisable.dialog.title",
        label: "resultDeviceTypeDisable.dialog.confirm",
        labelVariables: { representation: result.representation },
        confirmProps: {
            label: "action.disable",
            color: "warning",
            variant: "contained",
            endIcon: <PowerSettingsNew />,
            snackbarLabel: "resultDeviceTypeDisable.snackbar.success",
            snackbarLabelVariables: {
                result: result.representation,
            },
            endpoint: "/devicetype/" + result.id + "/disable",
            onSuccess: (defaultOnSuccess: () => void) => {
                defaultOnSuccess();
                reload();
            },
        },
    };

    return (
        <ButtonDialogAlertBoldConfirm
            {...{
                size: "small",
                startIcon: <PowerSettingsNew />,
                label: "action.disable",
                color: "warning",
                variant: "contained",
                deny: result?.deny,
                denyBehavior: "hide",
                denyKey: denyKey,
                dialogProps: _.merge(
                    internalDialogProps,
                    typeof dialogProps !== "undefined" ? dialogProps(result) : {}
                ) as ButtonDialogAlertBoldConfirmProps["dialogProps"],
                ...props,
            }}
        />
    );
};

export default ResultDeviceTypeDisable;
export { ResultDeviceTypeDisableDialogProps, ResultDeviceTypeDisableProps };
