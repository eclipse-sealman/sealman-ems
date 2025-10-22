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
import { Optional, BatchFormAlert, BatchFormAlertProps, Text } from "@arteneo/forge";

type BatchVariableDeleteProps = Optional<BatchFormAlertProps, "dialogProps">;

const BatchVariableDelete = (props: BatchVariableDeleteProps) => {
    return (
        <BatchFormAlert
            {...{
                label: "batch.device.variableDelete.action",
                ...props,
                dialogProps: {
                    title: "batch.device.variableDelete.title",
                    label: "batch.device.variableDelete.label",
                    formProps: {
                        fields: {
                            name: <Text {...{ required: true }} />,
                        },
                        endpoint: "/device/batch/variable/delete",
                    },
                    ...props.dialogProps,
                },
            }}
        />
    );
};

export default BatchVariableDelete;
export { BatchVariableDeleteProps };
