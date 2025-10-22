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

type BatchEnableProps = Optional<BatchAlertConfirmProps, "dialogProps">;

const BatchEnable = (props: BatchEnableProps) => {
    return (
        <BatchAlertConfirm
            {...{
                label: "batch.importFileRow.enable.action",
                ...props,
                dialogProps: {
                    label: "batch.importFileRow.enable.label",
                    ...props.dialogProps,
                    confirmProps: {
                        endpoint: "/importfilerow/batch/enable",
                        ...props.dialogProps?.confirmProps,
                    },
                },
            }}
        />
    );
};

export default BatchEnable;
export { BatchEnableProps };
