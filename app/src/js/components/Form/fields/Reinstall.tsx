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
import * as Yup from "yup";
import {
    Radio as MuiRadio,
    RadioGroup,
    RadioGroupProps,
    FormControl,
    FormControlProps,
    FormHelperText,
    FormLabel,
    FormLabelProps,
    FormControlLabel,
    FormControlLabelProps,
    Box,
} from "@mui/material";
import { FormikValues, FormikProps, useFormikContext, getIn } from "formik";
import { useTranslation } from "react-i18next";
import { useForm, OptionsType, FieldInterface, ButtonDialog } from "@arteneo/forge";
import { RemoveRedEyeOutlined } from "@mui/icons-material";
import TableReinstall, { TableReinstallProps } from "~app/components/Common/TableReinstall";

interface ReinstallSpecificProps {
    onChange?: (
        path: string,
        // eslint-disable-next-line
        setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void,
        event: React.ChangeEvent<HTMLInputElement>,
        value: string,
        onChange: () => void,
        values: FormikValues,
        name: string
    ) => void;
    formLabelProps?: FormLabelProps;
    radioGroupProps?: RadioGroupProps;
    formControlLabelProps?: FormControlLabelProps;
    formControlProps?: FormControlProps;
    tableReinstallProps?: TableReinstallProps;
}

type ReinstallProps = ReinstallSpecificProps & FieldInterface;

const Reinstall = ({
    onChange,
    formLabelProps,
    radioGroupProps,
    formControlLabelProps,
    formControlProps,
    tableReinstallProps,
    // eslint-disable-next-line
    validate: fieldValidate = (value: any, required: boolean) => {
        if (required && !Yup.string().required().isValidSync(value)) {
            return "validation.required";
        }

        return undefined;
    },
    ...field
}: ReinstallProps) => {
    const { t } = useTranslation();
    const {
        values,
        touched,
        errors,
        submitCount,
        setFieldValue,
        setFieldTouched,
        registerField,
        unregisterField,
    }: FormikProps<FormikValues> = useFormikContext();
    const { resolveField } = useForm();
    const { name, path, label, error, hasError, help, required, disabled, hidden, validate } = resolveField({
        values,
        touched,
        errors,
        submitCount,
        validate: fieldValidate,
        ...field,
    });

    React.useEffect(() => {
        if (hidden || typeof validate === "undefined") {
            return;
        }

        registerField(path, {
            validate: () => validate,
        });

        return () => {
            unregisterField(path);
        };
    }, [hidden, registerField, unregisterField, path, validate]);

    if (hidden) {
        return null;
    }

    const defaultOnChange = (event: React.ChangeEvent<HTMLInputElement>, value: string) => {
        if (value === "true") {
            setFieldValue(path, true);
        }

        if (value === "false") {
            setFieldValue(path, false);
        }
    };

    const callableOnChange = (event: React.ChangeEvent<HTMLInputElement>, value: string) => {
        if (onChange) {
            onChange(path, setFieldValue, event, value, () => defaultOnChange(event, value), values, name);
            return;
        }

        defaultOnChange(event, value);
    };

    const internalFormControlProps: FormControlProps = {
        error: hasError,
    };
    const mergedFormControlProps = Object.assign(internalFormControlProps, formControlProps);

    const internalRadioGroupProps: RadioGroupProps = {
        value: getIn(values, path, ""),
        row: true,
        onChange: callableOnChange,
        onBlur: () => setFieldTouched(path, true),
        sx: {
            alignItems: "center",
        },
    };
    const mergedRadioGroupProps = Object.assign(internalRadioGroupProps, radioGroupProps);

    const internalFormLabelProps: FormLabelProps = {
        required,
    };
    const mergedFormLabelProps = Object.assign(internalFormLabelProps, formLabelProps);

    let helperText: undefined | React.ReactNode = undefined;

    if (hasError || help) {
        helperText = (
            <>
                {error}
                {hasError && <br />}
                {help}
            </>
        );
    }

    const options: OptionsType = [
        {
            id: "false",
            representation: "radioTrueFalse.no",
        },
        {
            id: "true",
            representation: "radioTrueFalse.yes",
        },
    ];

    return (
        <Box {...{ sx: { display: "flex" } }}>
            <FormControl {...mergedFormControlProps}>
                {label && <FormLabel {...mergedFormLabelProps}>{label}</FormLabel>}
                <RadioGroup {...mergedRadioGroupProps}>
                    <ButtonDialog
                        {...{
                            label: "reinstall.action.showConnectedDevices",
                            color: "info",
                            variant: "contained",
                            startIcon: <RemoveRedEyeOutlined />,
                            sx: {
                                marginRight: 2,
                            },
                            dialogProps: {
                                title: "reinstall.dialog.title",
                                children: <TableReinstall {...tableReinstallProps} />,
                                dialogProps: {
                                    maxWidth: "lg",
                                },
                            },
                        }}
                    />
                    {options.map((option, key) => (
                        <FormControlLabel
                            key={key}
                            {...{
                                value: String(option.id),
                                control: <MuiRadio {...{ required, disabled }} />,
                                label: t(option.representation) as string,
                                ...formControlLabelProps,
                            }}
                        />
                    ))}
                </RadioGroup>
                {helperText && <FormHelperText>{helperText}</FormHelperText>}
            </FormControl>
        </Box>
    );
};

export default Reinstall;
export { ReinstallProps, ReinstallSpecificProps };
