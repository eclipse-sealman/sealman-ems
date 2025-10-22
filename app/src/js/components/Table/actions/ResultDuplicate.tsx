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
import { ContentCopyOutlined } from "@mui/icons-material";
import { ResultInterface, Optional, useTable } from "@arteneo/forge";
import ButtonDialogAlertBoldConfirm, {
    ButtonDialogAlertBoldConfirmProps,
} from "~app/components/Common/ButtonDialogAlertBoldConfirm";

type ResultDuplicateDialogProps = Optional<ButtonDialogAlertBoldConfirmProps["dialogProps"], "label">;

interface ResultDuplicateProps extends Omit<ButtonDialogAlertBoldConfirmProps, "dialogProps"> {
    result?: ResultInterface;
    dialogProps: (result: ResultInterface) => ResultDuplicateDialogProps;
}

const ResultDuplicate = ({ result, dialogProps, ...props }: ResultDuplicateProps) => {
    const { reload } = useTable();

    if (typeof result === "undefined") {
        throw new Error("ResultDuplicate component: Missing required result prop");
    }

    const internalDialogProps = {
        title: "resultDuplicate.dialog.title",
        label: "resultDuplicate.dialog.confirm",
        labelVariables: { representation: result.representation },
        confirmProps: {
            label: "action.duplicate",
            color: "success",
            variant: "contained",
            endIcon: <ContentCopyOutlined />,
            snackbarLabel: "resultDuplicate.snackbar.success",
            snackbarLabelVariables: {
                result: result.representation,
            },
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
                startIcon: <ContentCopyOutlined />,
                label: "action.duplicate",
                color: "warning",
                variant: "contained",
                deny: result?.deny,
                denyKey: "duplicate",
                dialogProps: _.merge(
                    internalDialogProps,
                    dialogProps(result)
                ) as ButtonDialogAlertBoldConfirmProps["dialogProps"],
                ...props,
            }}
        />
    );
};

export default ResultDuplicate;
export { ResultDuplicateDialogProps, ResultDuplicateProps };
