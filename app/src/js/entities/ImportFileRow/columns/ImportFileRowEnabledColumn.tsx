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

type ImportFileRowEnabledClumnProps = Optional<FormColumnProps, "formProps">;

const ImportFileRowEnabledColumn = (props: ImportFileRowEnabledClumnProps) => {
    return (
        <FormColumn
            {...{
                minWidth: 46,
                formProps: (row) => ({
                    fields: {
                        enabled: <Checkbox disableAutoLabel />,
                    },
                    initialValues: { enabled: row?.enabled },
                    endpoint: "/importfilerow/" + row.id + "/enabled",
                }),
                ...props,
            }}
        />
    );
};

export default ImportFileRowEnabledColumn;
export { ImportFileRowEnabledClumnProps };
