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
import { DeleteOutlined } from "@mui/icons-material";
import { ResultInterface, mergeEndpointCustomizer, Optional } from "@arteneo/forge";
import ResultButtonDialogAlertBoldConfirm, {
    ResultButtonDialogAlertBoldConfirmProps,
} from "~app/components/Table/actions/ResultButtonDialogAlertBoldConfirm";

type ResultDeleteDialogProps = Optional<ReturnType<ResultButtonDialogAlertBoldConfirmProps["dialogProps"]>, "label">;

interface ResultDeleteProps extends Omit<ResultButtonDialogAlertBoldConfirmProps, "dialogProps"> {
    dialogProps: (result: ResultInterface) => ResultDeleteDialogProps;
}

const ResultDelete = ({ result, dialogProps, ...props }: ResultDeleteProps) => {
    if (typeof result === "undefined") {
        throw new Error("ResultDelete component: Missing required result prop");
    }

    const internalDialogProps = {
        title: "resultDelete.dialog.title",
        label: "resultDelete.dialog.label",
        labelVariables: { representation: result.representation },
        alertProps: {
            severity: "error",
        },
        confirmProps: {
            label: "action.delete",
            color: "error",
            variant: "contained",
            endIcon: <DeleteOutlined />,
            snackbarLabel: "resultDelete.snackbar.success",
            snackbarLabelVariables: {
                result: result.representation,
            },
            endpoint: {
                method: "delete",
            },
        },
    };

    return (
        <ResultButtonDialogAlertBoldConfirm
            {...{
                result,
                size: "small",
                startIcon: <DeleteOutlined />,
                label: "action.delete",
                color: "error",
                variant: "contained",
                denyKey: "delete",
                dialogProps: (result: ResultInterface) =>
                    _.mergeWith(internalDialogProps, dialogProps(result), mergeEndpointCustomizer()),
                ...props,
            }}
        />
    );
};

export default ResultDelete;
export { ResultDeleteProps, ResultDeleteDialogProps };
