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
    IconButton,
    Tooltip,
} from "@mui/material";
import { Close } from "@mui/icons-material";
import { FormikValues, FormikProps, useFormikContext, getIn } from "formik";
import { useTranslation } from "react-i18next";
import { useForm, OptionsType, FieldInterface, Button, ButtonProps } from "@arteneo/forge";

interface RadioButtonSpecificProps {
    options: OptionsType;
    buttonProps?: ButtonProps;
    disableTranslateOption?: boolean;
    enableClear?: boolean;
    clearLabel?: string;
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
}

type RadioButtonProps = RadioButtonSpecificProps & FieldInterface;

const RadioButton = ({
    options,
    buttonProps,
    disableTranslateOption,
    enableClear = false,
    clearLabel = "radio.clear",
    onChange,
    formLabelProps,
    radioGroupProps,
    formControlLabelProps,
    formControlProps,
    // eslint-disable-next-line
    validate: fieldValidate = (value: any, required: boolean) => {
        if (required && !Yup.string().required().isValidSync(value)) {
            return "validation.required";
        }

        return undefined;
    },
    ...field
}: RadioButtonProps) => {
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

    let clearComponent: null | React.ReactElement = null;
    if (enableClear) {
        const clearValue = () => {
            setFieldValue(path, undefined);
        };

        clearComponent = (
            <IconButton onClick={() => clearValue()}>
                <Close />
            </IconButton>
        );

        if (typeof clearLabel !== "undefined") {
            // t(clearLabel) "?? clearLabel" is just for TypeScript
            clearComponent = <Tooltip title={t(clearLabel) ?? clearLabel}>{clearComponent}</Tooltip>;
        }
    }

    return (
        <FormControl {...mergedFormControlProps}>
            {label && <FormLabel {...mergedFormLabelProps}>{label}</FormLabel>}
            <RadioGroup {...mergedRadioGroupProps}>
                {options.map((option, key) => (
                    <FormControlLabel
                        key={key}
                        {...{
                            value: String(option.id),
                            // Disabled from <FormControlLabel /> is used by control component
                            disabled: disabled || option.disabled ? true : false,
                            control: <MuiRadio {...{ required }} />,
                            label: disableTranslateOption
                                ? option.representation // as string is just for TypeScript
                                : (t(option.representation) as string),
                            ...formControlLabelProps,
                        }}
                    />
                ))}
                {clearComponent}
                {typeof buttonProps !== "undefined" && <Button {...buttonProps} />}
            </RadioGroup>
            {helperText && <FormHelperText>{helperText}</FormHelperText>}
        </FormControl>
    );
};

export default RadioButton;
export { RadioButtonProps, RadioButtonSpecificProps };
