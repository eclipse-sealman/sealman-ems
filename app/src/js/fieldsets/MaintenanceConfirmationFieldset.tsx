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
import { FieldsInterface, renderField, TranslateVariablesInterface } from "@arteneo/forge";
import { Alert, Box } from "@mui/material";
import CrudFormView, { CrudFormViewProps } from "~app/views/CrudFormView";
import { useTranslation } from "react-i18next";

interface MaintenanceConfirmationFieldsetProps extends Omit<CrudFormViewProps, "children"> {
    label?: string;
    labelVariables?: TranslateVariablesInterface;
    labelWarning?: string;
    labelWarningVariables?: TranslateVariablesInterface;
    fields: FieldsInterface;
}

const MaintenanceConfirmationFieldset = ({
    fields,
    label,
    labelVariables = {},
    labelWarning,
    labelWarningVariables = {},
    ...formViewProps
}: MaintenanceConfirmationFieldsetProps) => {
    const { t } = useTranslation();

    const render = renderField(fields);

    return (
        <CrudFormView {...formViewProps}>
            <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                {label && <Alert {...{ severity: "info" }}>{t(label, labelVariables)}</Alert>}
                {labelWarning && <Alert {...{ severity: "warning" }}>{t(labelWarning, labelWarningVariables)}</Alert>}
                {Object.keys(fields).map((field) => render(field))}
            </Box>
        </CrudFormView>
    );
};

export default MaintenanceConfirmationFieldset;
export { MaintenanceConfirmationFieldsetProps };
