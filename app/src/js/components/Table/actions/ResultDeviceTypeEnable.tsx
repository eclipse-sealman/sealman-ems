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

type ResultDeviceTypeEnableDialogProps = Optional<ButtonDialogAlertBoldConfirmProps["dialogProps"], "label">;

type InternalResultDeviceTypeEnableProps = Omit<ButtonDialogAlertBoldConfirmProps, "dialogProps">;

interface ResultDeviceTypeEnableProps extends InternalResultDeviceTypeEnableProps {
    result?: ResultInterface;
    dialogProps?: (result: ResultInterface) => ResultDeviceTypeEnableDialogProps;
}

const ResultDeviceTypeEnable = ({ result, dialogProps, denyKey = "enable", ...props }: ResultDeviceTypeEnableProps) => {
    const { reload } = useTable();

    if (typeof result === "undefined") {
        throw new Error("ResultDeviceTypeEnable component: Missing required result prop");
    }

    if (result?.deny?.[denyKey] && !result?.deny?.[denyKey]?.endsWith(".cannotEnable")) {
        return null;
    }

    const internalDialogProps = {
        title: "resultDeviceTypeEnable.dialog.title",
        label: "resultDeviceTypeEnable.dialog.confirm",
        labelVariables: { representation: result.representation },
        confirmProps: {
            label: "action.enable",
            color: "success",
            variant: "contained",
            endIcon: <PowerSettingsNew />,
            snackbarLabel: "resultDeviceTypeEnable.snackbar.success",
            snackbarLabelVariables: {
                result: result.representation,
            },
            endpoint: "/devicetype/" + result.id + "/enable",
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
                label: "action.enable",
                color: "success",
                variant: "contained",
                deny: result?.deny,
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

export default ResultDeviceTypeEnable;
export { ResultDeviceTypeEnableDialogProps, ResultDeviceTypeEnableProps };
