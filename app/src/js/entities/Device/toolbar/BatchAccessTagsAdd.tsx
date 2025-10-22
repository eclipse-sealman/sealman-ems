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

type BatchAccessTagsAddProps = Optional<BatchFormAlertProps, "dialogProps">;

const BatchAccessTagsAdd = (props: BatchAccessTagsAddProps) => {
    return (
        <BatchFormAlert
            {...{
                label: "batch.device.accessTagsAdd.action",
                ...props,
                dialogProps: {
                    title: "batch.device.accessTagsAdd.title",
                    label: "batch.device.accessTagsAdd.label",
                    formProps: {
                        fields: {
                            accessTags: <MultiselectApi {...{ required: true, endpoint: "/options/access/tags" }} />,
                        },
                        endpoint: "/device/batch/accesstags/add",
                    },
                    ...props.dialogProps,
                },
            }}
        />
    );
};

export default BatchAccessTagsAdd;
export { BatchAccessTagsAddProps };
