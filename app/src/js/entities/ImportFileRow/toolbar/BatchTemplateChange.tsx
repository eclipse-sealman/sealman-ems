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
import { Optional, BatchFormAlert, BatchFormAlertProps } from "@arteneo/forge";
import SelectApiGroupedByDeviceType from "~app/components/Form/fields/SelectApiGroupedByDeviceType";

type BatchTemplateChangeProps = Optional<BatchFormAlertProps, "dialogProps">;

const BatchTemplateChange = (props: BatchTemplateChangeProps) => {
    return (
        <BatchFormAlert
            {...{
                label: "batch.importFileRow.templateChange.action",
                ...props,
                dialogProps: {
                    title: "batch.importFileRow.templateChange.title",
                    label: "batch.importFileRow.templateChange.label",
                    formProps: {
                        fields: {
                            template: <SelectApiGroupedByDeviceType {...{ endpoint: "/options/templates" }} />,
                        },
                        endpoint: "/importfilerow/batch/template/change",
                    },
                    ...props.dialogProps,
                },
            }}
        />
    );
};

export default BatchTemplateChange;
export { BatchTemplateChangeProps };
