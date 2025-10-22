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
import { FieldsInterface, renderField } from "@arteneo/forge";
import { Box } from "@mui/material";
import CrudFormView, { CrudFormViewProps } from "~app/views/CrudFormView";

interface CrudFieldsetProps extends Omit<CrudFormViewProps, "children"> {
    fields: FieldsInterface;
}

const CrudFieldset = ({ fields, ...formViewProps }: CrudFieldsetProps) => {
    const render = renderField(fields);

    return (
        <CrudFormView {...formViewProps}>
            <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                {Object.keys(fields).map((field) => render(field))}
            </Box>
        </CrudFormView>
    );
};

export default CrudFieldset;
export { CrudFieldsetProps };
