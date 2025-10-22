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
import { Box } from "@mui/material";
import { useFormikContext } from "formik";
import { Button, useTable, FieldsInterface, renderField } from "@arteneo/forge";
import { CheckOutlined, ClearOutlined } from "@mui/icons-material";

interface TableFiltersFieldsetProps {
    fields: FieldsInterface;
}

const TableFiltersFieldset = ({ fields }: TableFiltersFieldsetProps) => {
    const { isSubmitting, setFieldValue } = useFormikContext();
    const { clearFilters } = useTable();

    const render = renderField(fields);

    const gridTemplateColumns = {
        xs: "minmax(0, 1fr)",
        md: "repeat(3, minmax(0,1fr))",
    };

    const fieldsCount = Object.keys(fields).length;
    switch (fieldsCount) {
        case 1:
            gridTemplateColumns["md"] = "minmax(0, 1fr)";
            break;
        case 2:
            gridTemplateColumns["md"] = "repeat(2, minmax(0,1fr))";
            break;
    }

    return (
        <>
            <Box sx={{ display: "grid", gap: 2, gridTemplateColumns }}>
                {Object.keys(fields).map((field) => render(field))}
            </Box>
            <Box mt={3} sx={{ display: "flex", justifyContent: "space-between" }}>
                <Button
                    {...{
                        onClick: () => clearFilters(setFieldValue),
                        disabled: isSubmitting,
                        variant: "contained",
                        color: "info",
                        label: "table.filters.clear",
                        startIcon: <ClearOutlined />,
                    }}
                />
                <Button
                    {...{
                        loading: isSubmitting,
                        type: "submit",
                        variant: "contained",
                        color: "success",
                        label: "table.filters.submit",
                        endIcon: <CheckOutlined />,
                    }}
                />
            </Box>
        </>
    );
};

export default TableFiltersFieldset;
export { TableFiltersFieldsetProps };
