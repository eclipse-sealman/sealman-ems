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
import { getIn } from "formik";
import { ColumnPathInterface, Form, FormProps, Optional, ResultInterface, useLoader } from "@arteneo/forge";
import ColumnFieldset from "~app/fieldsets/ColumnFieldset";

type FormColumnFormProps = Optional<FormProps, "children"> & ColumnPathInterface;

interface FormColumnProps extends ColumnPathInterface {
    formProps: (result: ResultInterface) => null | FormColumnFormProps;
    minWidth?: number;
}

const FormColumn = ({ result, columnName, path, formProps: internalFormProps, minWidth = 210 }: FormColumnProps) => {
    const { hideLoader } = useLoader();

    if (typeof columnName === "undefined") {
        throw new Error("FormColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("FormColumn component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;
    if (!value) {
        return null;
    }

    const formProps = internalFormProps(value);
    if (formProps === null) {
        return null;
    }

    return (
        <Form
            // Passing key based on stringified initialValues forces complete rerender when those values are changed. This prevents sending request by <ColumnFieldset /> (it does every time values change, but not on initial render). This is a case when you change values from outside (in our case through batch actions). This solution is blunt, but easy
            key={JSON.stringify(formProps.initialValues)}
            {...{
                onSubmitSuccess: (defaultOnSubmitSuccess, values, helpers) => {
                    hideLoader();
                    helpers.setSubmitting(false);
                },
                children: <ColumnFieldset {...{ fields: formProps.fields, minWidth }} />,
                ...formProps,
            }}
        />
    );
};

export default FormColumn;
export { FormColumnProps };
