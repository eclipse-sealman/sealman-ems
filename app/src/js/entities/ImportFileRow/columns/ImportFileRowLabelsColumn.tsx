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
import { Multiselect, Optional, OptionInterface } from "@arteneo/forge";
import { useImportFile } from "~app/contexts/ImportFile";
import FormColumn, { FormColumnProps } from "~app/components/Table/columns/FormColumn";

type ImportFileRowLabelsColumnProps = Optional<FormColumnProps, "formProps">;

const ImportFileRowLabelsColumn = (props: ImportFileRowLabelsColumnProps) => {
    const { labels } = useImportFile();

    return (
        <FormColumn
            {...{
                minWidth: 230,
                formProps: (row) => ({
                    fields: {
                        labels: (
                            <Multiselect
                                {...{
                                    options: labels,
                                    disableTranslateOption: true,
                                }}
                            />
                        ),
                    },
                    initialValues: { labels: row?.labels?.map((label: OptionInterface) => label.id) },
                    endpoint: "/importfilerow/" + row.id + "/labels",
                }),
                ...props,
            }}
        />
    );
};

export default ImportFileRowLabelsColumn;
export { ImportFileRowLabelsColumnProps };
