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

type ImportFileRowReinstallConfig3ClumnProps = Optional<FormColumnProps, "formProps">;

const ImportFileRowReinstallConfig3Column = (props: ImportFileRowReinstallConfig3ClumnProps) => {
    return (
        <FormColumn
            {...{
                minWidth: 46,
                formProps: (row) => {
                    const deviceType = row.deviceType;
                    if (!deviceType?.hasConfig3 || deviceType?.hasAlwaysReinstallConfig3) {
                        return null;
                    }

                    return {
                        fields: {
                            reinstallConfig3: <Checkbox disableAutoLabel />,
                        },
                        initialValues: { reinstallConfig3: row?.reinstallConfig3 },
                        endpoint: "/importfilerow/" + row.id + "/reinstallconfig3",
                    };
                },
                ...props,
            }}
        />
    );
};

export default ImportFileRowReinstallConfig3Column;
export { ImportFileRowReinstallConfig3ClumnProps };
