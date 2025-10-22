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
import { Optional, BatchFormAlert, BatchFormAlertProps, FieldEndpointType, RadioFalseTrue } from "@arteneo/forge";
import { useTranslation } from "react-i18next";

interface BatchFlagProps extends Optional<BatchFormAlertProps, "dialogProps"> {
    prefix: string;
    endpoint: FieldEndpointType;
}

const BatchFlag = ({ prefix, endpoint, ...props }: BatchFlagProps) => {
    const { t } = useTranslation();

    return (
        <BatchFormAlert
            {...{
                label: prefix + ".action",
                ...props,
                dialogProps: {
                    title: prefix + ".title",
                    label: prefix + ".label",
                    ...props.dialogProps,
                    formProps: {
                        fields: {
                            flag: (
                                <RadioFalseTrue
                                    {...{ label: t(prefix + ".flag"), disableTranslateLabel: true, required: true }}
                                />
                            ),
                        },
                        endpoint,
                    },
                },
            }}
        />
    );
};

export default BatchFlag;
export { BatchFlagProps };
