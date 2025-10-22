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
import { useTranslation } from "react-i18next";
import { Button, FieldsInterface, renderField } from "@arteneo/forge";
import { Alert, Box } from "@mui/material";
import { DownloadOutlined } from "@mui/icons-material";
import CrudFormView, { CrudFormViewProps } from "~app/views/CrudFormView";

interface ImportFileFieldsetProps extends Omit<CrudFormViewProps, "children"> {
    fields: FieldsInterface;
}

const ImportFileFieldset = ({ fields, ...formViewProps }: ImportFileFieldsetProps) => {
    const { t } = useTranslation();

    const render = renderField(fields);

    return (
        <CrudFormView {...formViewProps}>
            <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                <Alert {...{ severity: "info" }}>
                    <Box {...{ display: "flex", flexDirection: "column", gap: 1, mb: 2 }}>
                        <Box>{t("importFile.alert.information")}</Box>
                        <Box>{t("importFile.alert.note")}</Box>
                        <Box>{t("importFile.alert.download")}</Box>
                    </Box>
                    <Button
                        {...{
                            label: "importFile.download",
                            component: "a",
                            href: "/import_devices_example_file.xlsx",
                            target: "_blank",
                            startIcon: <DownloadOutlined />,
                            color: "warning",
                            variant: "contained",
                        }}
                    />
                </Alert>
                {Object.keys(fields).map((field) => render(field))}
            </Box>
        </CrudFormView>
    );
};

export default ImportFileFieldset;
export { ImportFileFieldsetProps };
