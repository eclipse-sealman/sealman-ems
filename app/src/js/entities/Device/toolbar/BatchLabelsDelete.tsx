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
import { Optional, BatchFormAlert, BatchFormAlertProps, MultiselectApi } from "@arteneo/forge";

type BatchLabelsDeleteProps = Optional<BatchFormAlertProps, "dialogProps">;

const BatchLabelsDelete = (props: BatchLabelsDeleteProps) => {
    return (
        <BatchFormAlert
            {...{
                label: "batch.device.labelsDelete.action",
                ...props,
                dialogProps: {
                    title: "batch.device.labelsDelete.title",
                    label: "batch.device.labelsDelete.label",
                    formProps: {
                        fields: {
                            labels: <MultiselectApi {...{ required: true, endpoint: "/options/labels" }} />,
                        },
                        endpoint: "/device/batch/labels/delete",
                    },
                    ...props.dialogProps,
                },
            }}
        />
    );
};

export default BatchLabelsDelete;
export { BatchLabelsDeleteProps };
