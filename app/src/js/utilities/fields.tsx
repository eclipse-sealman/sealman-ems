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

import { FormikErrors, FormikTouched, FormikValues, getIn } from "formik";

// eslint-disable-next-line
export const showOnEqual = (fieldName: string, value?: any) => ({
    hidden: (values: FormikValues) => (values?.[fieldName] === value ? false : true),
});

// eslint-disable-next-line
export const showAndRequireOnEqual = (fieldName: string, value?: any) => ({
    hidden: (values: FormikValues) => (values?.[fieldName] === value ? false : true),
    required: (values: FormikValues) => (values?.[fieldName] === value ? true : false),
});

export const showOnTrue = (fieldName: string) => ({
    hidden: (values: FormikValues) => (values?.[fieldName] ? false : true),
});

export const showAndRequireOnTrue = (fieldName: string) => ({
    hidden: (values: FormikValues) => (values?.[fieldName] ? false : true),
    required: (values: FormikValues) => (values?.[fieldName] ? true : false),
});

export const showOnFalse = (fieldName: string) => ({
    hidden: (values: FormikValues) => (values?.[fieldName] ? true : false),
});

export const showAndRequireOnFalse = (fieldName: string) => ({
    hidden: (values: FormikValues) => (values?.[fieldName] ? true : false),
    required: (values: FormikValues) => (values?.[fieldName] ? false : true),
});

export const hasAtLeastOneRequirementsMet = (values: FormikValues, requirements: FormikValues): boolean => {
    for (const fieldName of Object.keys(requirements)) {
        if (values?.[fieldName] === requirements[fieldName]) {
            return true;
        }
    }

    return false;
};

export const hasAllRequirementsMet = (values: FormikValues, requirements: FormikValues): boolean => {
    for (const fieldName of Object.keys(requirements)) {
        if (values?.[fieldName] !== requirements[fieldName]) {
            return false;
        }
    }

    return true;
};

export const showOn = (requirements: FormikValues) => ({
    hidden: (values: FormikValues) => !hasAllRequirementsMet(values, requirements),
});

export const showAndRequireOn = (requirements: FormikValues) => ({
    hidden: (values: FormikValues) => !hasAllRequirementsMet(values, requirements),
    required: (values: FormikValues) => hasAllRequirementsMet(values, requirements),
});

export const collectionShowOnTrue = (fieldName: string) => ({
    hidden: (
        values: FormikValues,
        touched: FormikTouched<FormikValues>,
        errors: FormikErrors<FormikValues>,
        name: string
    ) => {
        const prefix = name.substring(0, name.lastIndexOf(".") < 0 ? 0 : name.lastIndexOf(".") + 1);
        const value = getIn(values, prefix + fieldName, []);
        return value == true ? false : true;
    },
});

export const collectionShowAndRequireOnTrue = (fieldName: string) => ({
    hidden: (
        values: FormikValues,
        touched: FormikTouched<FormikValues>,
        errors: FormikErrors<FormikValues>,
        name: string
    ) => {
        const prefix = name.substring(0, name.lastIndexOf(".") < 0 ? 0 : name.lastIndexOf(".") + 1);
        const value = getIn(values, prefix + fieldName, []);
        return value == true ? false : true;
    },
    required: (
        values: FormikValues,
        touched: FormikTouched<FormikValues>,
        errors: FormikErrors<FormikValues>,
        name: string
    ) => {
        const prefix = name.substring(0, name.lastIndexOf(".") < 0 ? 0 : name.lastIndexOf(".") + 1);
        const value = getIn(values, prefix + fieldName, []);
        return value == true ? true : false;
    },
});
