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

type BatchLabelsAddProps = Optional<BatchFormAlertProps, "dialogProps">;

const BatchLabelsAdd = (props: BatchLabelsAddProps) => {
    return (
        <BatchFormAlert
            {...{
                label: "batch.importFileRow.labelsAdd.action",
                ...props,
                dialogProps: {
                    title: "batch.importFileRow.labelsAdd.title",
                    label: "batch.importFileRow.labelsAdd.label",
                    formProps: {
                        fields: {
                            labels: <MultiselectApi {...{ required: true, endpoint: "/options/labels" }} />,
                        },
                        endpoint: "/importfilerow/batch/labels/add",
                    },
                    ...props.dialogProps,
                },
            }}
        />
    );
};

export default BatchLabelsAdd;
export { BatchLabelsAddProps };
