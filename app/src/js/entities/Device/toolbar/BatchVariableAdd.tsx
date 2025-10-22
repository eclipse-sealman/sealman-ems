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
import { Optional, BatchFormAlert, BatchFormAlertProps, Text, Textarea } from "@arteneo/forge";

type BatchVariableAddProps = Optional<BatchFormAlertProps, "dialogProps">;

const BatchVariableAdd = (props: BatchVariableAddProps) => {
    return (
        <BatchFormAlert
            {...{
                label: "batch.device.variableAdd.action",
                ...props,
                dialogProps: {
                    title: "batch.device.variableAdd.title",
                    label: "batch.device.variableAdd.label",
                    formProps: {
                        fields: {
                            name: <Text {...{ required: true }} />,
                            variableValue: <Textarea {...{ required: true, fieldProps: { minRows: 1 } }} />,
                        },
                        endpoint: "/device/batch/variable/add",
                    },
                    ...props.dialogProps,
                },
            }}
        />
    );
};

export default BatchVariableAdd;
export { BatchVariableAddProps };
