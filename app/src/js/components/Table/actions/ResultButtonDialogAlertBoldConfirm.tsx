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
import { ColumnActionPathInterface, ResultInterface, useTable } from "@arteneo/forge";
import ButtonDialogAlertBoldConfirm, {
    ButtonDialogAlertBoldConfirmProps,
} from "~app/components/Common/ButtonDialogAlertBoldConfirm";

interface ResultButtonDialogAlertBoldConfirmSpecificProps {
    disableOnSuccessReload?: boolean;
    dialogProps: (result: ResultInterface) => ButtonDialogAlertBoldConfirmProps["dialogProps"];
}

type ResultButtonDialogAlertBoldConfirmProps = ResultButtonDialogAlertBoldConfirmSpecificProps &
    Omit<ButtonDialogAlertBoldConfirmProps, "dialogProps"> &
    ColumnActionPathInterface;

const ResultButtonDialogAlertBoldConfirm = ({
    disableOnSuccessReload,
    dialogProps,
    result,
    path,
    ...props
}: ResultButtonDialogAlertBoldConfirmProps) => {
    const { reload } = useTable();

    if (typeof result === "undefined") {
        throw new Error("ResultButtonDialogAlertBoldConfirm component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;

    const resolvedDialogProps = dialogProps(value);
    // We need to reference something else then onSuccess in resolvedDialogProps to avoid recurrence (onSuccess will be overridden)
    const resolvedOnSuccess = resolvedDialogProps?.confirmProps?.onSuccess;

    const onSuccess: ButtonDialogAlertBoldConfirmProps["dialogProps"]["confirmProps"]["onSuccess"] = (
        defaultOnSuccess,
        response,
        setLoading
    ) => {
        const internalDefaultOnSuccess = () => {
            defaultOnSuccess();

            if (!disableOnSuccessReload) {
                reload();
            }
        };

        if (typeof resolvedOnSuccess !== "undefined") {
            resolvedOnSuccess(internalDefaultOnSuccess, response, setLoading);
            return;
        }

        internalDefaultOnSuccess();
    };

    return (
        <ButtonDialogAlertBoldConfirm
            {...{
                deny: result?.deny,
                // Override confirmProps.onSuccess with internal one to include reload logic in defaultOnSuccess
                dialogProps: _.mergeWith(resolvedDialogProps, {
                    confirmProps: {
                        onSuccess,
                    },
                }) as ButtonDialogAlertBoldConfirmProps["dialogProps"],
                ...props,
            }}
        />
    );
};

export default ResultButtonDialogAlertBoldConfirm;
export { ResultButtonDialogAlertBoldConfirmProps, ResultButtonDialogAlertBoldConfirmSpecificProps };
