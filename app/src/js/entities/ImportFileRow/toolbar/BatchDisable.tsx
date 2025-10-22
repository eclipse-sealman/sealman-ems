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
import { Optional, BatchAlertConfirm, BatchAlertConfirmProps } from "@arteneo/forge";

type BatchDisableProps = Optional<BatchAlertConfirmProps, "dialogProps">;

const BatchDisable = (props: BatchDisableProps) => {
    return (
        <BatchAlertConfirm
            {...{
                label: "batch.importFileRow.disable.action",
                ...props,
                dialogProps: {
                    label: "batch.importFileRow.disable.label",
                    ...props.dialogProps,
                    confirmProps: {
                        endpoint: "/importfilerow/batch/disable",
                        ...props.dialogProps?.confirmProps,
                    },
                },
            }}
        />
    );
};

export default BatchDisable;
export { BatchDisableProps };
