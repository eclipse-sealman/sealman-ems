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
import ButtonDialogUrl, { ButtonDialogUrlProps } from "~app/components/Common/ButtonDialogUrl";

type InternalResultDialogUrlProps = Omit<ButtonDialogUrlProps, "dialogProps"> & ColumnActionPathInterface;

interface ResultDialogUrlProps extends InternalResultDialogUrlProps {
    dialogProps: (result: ResultInterface) => ButtonDialogUrlProps["dialogProps"];
}

const ResultDialogUrl = ({ result, dialogProps, path, ...props }: ResultDialogUrlProps) => {
    const [showDialog, setShowDialog] = React.useState(false);

    if (typeof result === "undefined") {
        throw new Error("ResultDialogUrl component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;

    return (
        <ButtonDialogUrl
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

export default ResultDialogUrl;
export { ResultDialogUrlProps };
