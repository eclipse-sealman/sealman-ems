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
import { Optional } from "@arteneo/forge";
import ResultDialogMonaco, { ResultDialogMonacoProps } from "~app/components/Table/actions/ResultDialogMonaco";

type ResultDialogMessageProps = Optional<ResultDialogMonacoProps, "dialogProps">;

const ResultDialogMessage = ({ result, ...props }: ResultDialogMessageProps) => {
    if (typeof result === "undefined") {
        throw new Error("ResultDialogMessage component: Missing required result prop");
    }

    return (
        <ResultDialogMonaco
            {...{
                result,
                label: "resultDialogMessage.action",
                dialogProps: (result) => ({
                    title: "resultDialogMessage.dialog.title",
                    content: result?.message,
                }),
                ...props,
            }}
        />
    );
};

export default ResultDialogMessage;
export { ResultDialogMessageProps };
