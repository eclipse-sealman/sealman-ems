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

type ResultShowPreviousSecretValueProps = Omit<ResultDialogMonacoProps, "dialogProps">;

const ResultShowPreviousSecretValue = ({ ...props }: ResultShowPreviousSecretValueProps) => {
    return (
        <ResultDialogMonaco
            {...{
                label: "action.showPreviousSecretValue",
                denyKey: "showPrevious",
                denyBehavior: "hide",
                icon: <RemoveRedEyeOutlined />,
                dialogProps: (result) => ({
                    title: "secretLog.dialogPrevious.title",
                    copyToClipboardProps: {
                        snackbarLabel: "secretLog.dialogPrevious.snackbar.copyToClipboardSuccess",
                        content: (payload) => payload?.previousSecretValue,
                    },
                    initializeEndpoint: "/secretlog/" + result?.id + "/show/previous/secret",
                    content: (payload) => payload?.previousSecretValue,
                }),
                ...props,
            }}
        />
    );
};

export default ResultShowPreviousSecretValue;
export { ResultShowPreviousSecretValueProps };
