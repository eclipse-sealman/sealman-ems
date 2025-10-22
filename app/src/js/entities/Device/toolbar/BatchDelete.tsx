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
import { Optional, BatchAlertConfirmMulti, BatchAlertConfirmMultiProps } from "@arteneo/forge";

type BatchDeleteProps = Optional<BatchAlertConfirmMultiProps, "dialogProps">;

const BatchDelete = (props: BatchDeleteProps) => {
    return (
        <BatchAlertConfirmMulti
            {...{
                label: "batch.device.delete.action",
                ...props,
                dialogProps: {
                    label: "batch.device.delete.label",
                    ...props.dialogProps,
                    confirmProps: {
                        endpoint: (result) => ({
                            method: "delete",
                            url: "/device/" + result.id,
                        }),
                        resultDenyKey: "delete",
                        ...props.dialogProps?.confirmProps,
                    },
                },
            }}
        />
    );
};

export default BatchDelete;
export { BatchDeleteProps };
