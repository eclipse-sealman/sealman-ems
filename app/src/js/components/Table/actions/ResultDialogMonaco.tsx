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
import { ResultInterface, ColumnActionPathInterface } from "@arteneo/forge";
import ButtonDialogMonaco, { ButtonDialogMonacoProps } from "~app/components/Common/ButtonDialogMonaco";

type InternalResultDialogMonacoProps = Omit<ButtonDialogMonacoProps, "dialogProps"> & ColumnActionPathInterface;

interface ResultDialogMonacoProps extends InternalResultDialogMonacoProps {
    dialogProps: (result: ResultInterface) => ButtonDialogMonacoProps["dialogProps"];
}

const ResultDialogMonaco = ({ result, dialogProps, path, ...props }: ResultDialogMonacoProps) => {
    const [showDialog, setShowDialog] = React.useState(false);

    if (typeof result === "undefined") {
        throw new Error("ResultDialogMonaco component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;

    return (
        <ButtonDialogMonaco
            {...{
                deny: value?.deny,
                open: showDialog,
                onClose: () => setShowDialog(false),
                dialogProps: dialogProps(value),
                ...props,
            }}
        />
    );
};

export default ResultDialogMonaco;
export { ResultDialogMonacoProps };
