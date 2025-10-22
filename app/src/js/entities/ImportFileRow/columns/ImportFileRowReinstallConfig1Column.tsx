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
import { Checkbox, Optional } from "@arteneo/forge";
import FormColumn, { FormColumnProps } from "~app/components/Table/columns/FormColumn";

type ImportFileRowReinstallConfig1ColumnProps = Optional<FormColumnProps, "formProps">;

const ImportFileRowReinstallConfig1Column = (props: ImportFileRowReinstallConfig1ColumnProps) => {
    return (
        <FormColumn
            {...{
                minWidth: 46,
                formProps: (row) => {
                    const deviceType = row.deviceType;
                    if (!deviceType?.hasConfig1 || deviceType?.hasAlwaysReinstallConfig1) {
                        return null;
                    }

                    return {
                        fields: {
                            reinstallConfig1: <Checkbox disableAutoLabel />,
                        },
                        initialValues: { reinstallConfig1: row?.reinstallConfig1 },
                        endpoint: "/importfilerow/" + row.id + "/reinstallconfig1",
                    };
                },
                ...props,
            }}
        />
    );
};

export default ImportFileRowReinstallConfig1Column;
export { ImportFileRowReinstallConfig1ColumnProps };
