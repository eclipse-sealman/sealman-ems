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
import { FormikValues, useFormikContext } from "formik";
import { useTranslation } from "react-i18next";
import { Alert, Box } from "@mui/material";
import { FieldsInterface, renderField, useForm } from "@arteneo/forge";
import CrudFormView, { CrudFormViewProps } from "~app/views/CrudFormView";
import { useUser } from "~app/contexts/User";

interface UserEditFieldsetProps extends Omit<CrudFormViewProps, "children"> {
    fields: FieldsInterface;
}

const UserEditFieldset = ({ fields, ...formViewProps }: UserEditFieldsetProps) => {
    const { t } = useTranslation();
    const { username } = useUser();
    const { formikInitialValues } = useForm();
    const { values } = useFormikContext<FormikValues>();
    const render = renderField(fields);

    // Use initial values from form for to determine whether logged in user is the one we are editing
    const isWarningApplicable = username === formikInitialValues?.username ? true : false;
    const isWarningVisible = isWarningApplicable && values?.username !== formikInitialValues?.username ? true : false;

    const remainingFieldKeys = Object.keys(fields).filter((fieldKey) => fieldKey !== "username");

    return (
        <CrudFormView {...formViewProps}>
            <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                {render("username")}
                {isWarningVisible && <Alert severity="warning">{t("userEdit.usernameEditAlert")}</Alert>}
                {remainingFieldKeys.map((field) => render(field))}
            </Box>
        </CrudFormView>
    );
};

export default UserEditFieldset;
export { UserEditFieldsetProps };
