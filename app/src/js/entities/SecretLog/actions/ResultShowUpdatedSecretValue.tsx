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
import ResultDialogMonaco, { ResultDialogMonacoProps } from "~app/components/Table/actions/ResultDialogMonaco";
import { RemoveRedEyeOutlined } from "@mui/icons-material";

type ResultShowUpdatedSecretValueProps = Omit<ResultDialogMonacoProps, "dialogProps">;

const ResultShowUpdatedSecretValue = ({ ...props }: ResultShowUpdatedSecretValueProps) => {
    return (
        <ResultDialogMonaco
            {...{
                label: "action.showUpdatedSecretValue",
                denyKey: "showUpdated",
                denyBehavior: "hide",
                icon: <RemoveRedEyeOutlined />,
                dialogProps: (result) => ({
                    title: "secretLog.dialogUpdated.title",
                    copyToClipboardProps: {
                        snackbarLabel: "secretLog.dialogUpdated.snackbar.copyToClipboardSuccess",
                        content: (payload) => payload?.updatedSecretValue,
                    },
                    initializeEndpoint: "/secretlog/" + result?.id + "/show/updated/secret",
                    content: (payload) => payload?.updatedSecretValue,
                }),
                ...props,
            }}
        />
    );
};

export default ResultShowUpdatedSecretValue;
export { ResultShowUpdatedSecretValueProps };
