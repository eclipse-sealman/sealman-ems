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
import { Optional } from "@arteneo/forge";
import { useImportFile } from "~app/contexts/ImportFile";
import FormColumn, { FormColumnProps } from "~app/components/Table/columns/FormColumn";
import SelectGroupedByDeviceType from "~app/components/Form/fields/SelectGroupedByDeviceType";

type ImportFileRowTemplateColumnProps = Optional<FormColumnProps, "formProps">;

const ImportFileRowTemplateColumn = (props: ImportFileRowTemplateColumnProps) => {
    const { getDeviceTypeTemplates } = useImportFile();

    return (
        <FormColumn
            {...{
                formProps: (row) => {
                    const deviceType = row.deviceType;
                    if (!deviceType?.hasTemplates) {
                        return null;
                    }

                    return {
                        fields: {
                            template: (
                                <SelectGroupedByDeviceType
                                    {...{
                                        options: getDeviceTypeTemplates(deviceType.id),
                                        disableTranslateOption: true,
                                    }}
                                />
                            ),
                        },
                        initialValues: { template: row.template?.id },
                        endpoint: "/importfilerow/" + row.id + "/template",
                    };
                },
                ...props,
            }}
        />
    );
};

export default ImportFileRowTemplateColumn;
export { ImportFileRowTemplateColumnProps };
