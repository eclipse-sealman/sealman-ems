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
import { Alert, Box } from "@mui/material";
import { useTranslation } from "react-i18next";
import { useConfiguration } from "~app/contexts/Configuration";
import CrudFormView, { CrudFormViewProps } from "~app/views/CrudFormView";
import { FormikValues, useFormikContext } from "formik";

interface ConfigurationTotpFieldsetProps extends Omit<CrudFormViewProps, "children"> {
    fields: FieldsInterface;
}

const ConfigurationTotpFieldset = ({ fields, ...formViewProps }: ConfigurationTotpFieldsetProps) => {
    const { t } = useTranslation();
    const { isTotpSecretGenerated } = useConfiguration();
    const { values } = useFormikContext<FormikValues>();

    const render = renderField(fields);

    const remainingFieldKeys = Object.keys(fields).filter((fieldKey) => fieldKey !== "totpEnabled");

    return (
        <CrudFormView {...formViewProps}>
            <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                {render("totpEnabled")}
                {values?.totpEnabled && isTotpSecretGenerated && (
                    <Alert {...{ severity: "warning", sx: { mb: 1, mt: -1 } }}>
                        {t("configuration.totp.isTotpSecretGeneratedAlert")}
                    </Alert>
                )}
                {remainingFieldKeys.map((field) => render(field))}
            </Box>
        </CrudFormView>
    );
};

export default ConfigurationTotpFieldset;
export { ConfigurationTotpFieldsetProps };
