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
import CrudFormViewOnlySubmit, { CrudFormViewOnlySubmitProps } from "~app/views/CrudFormViewOnlySubmit";

interface CrudOnlySubmitFieldsetProps extends Omit<CrudFormViewOnlySubmitProps, "children"> {
    fields: FieldsInterface;
}

const CrudOnlySubmitFieldset = ({ fields, ...formViewProps }: CrudOnlySubmitFieldsetProps) => {
    const render = renderField(fields);

    return (
        <CrudFormViewOnlySubmit {...formViewProps}>
            <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                {Object.keys(fields).map((field) => render(field))}
            </Box>
        </CrudFormViewOnlySubmit>
    );
};

export default CrudOnlySubmitFieldset;
export { CrudOnlySubmitFieldsetProps };
