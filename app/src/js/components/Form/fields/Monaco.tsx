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
import { editor } from "monaco-editor/esm/vs/editor/editor.api";
import * as Yup from "yup";
import { FormControl, FormControlProps, FormHelperText, FormLabel, FormLabelProps, Box, Chip } from "@mui/material";
import { FormikValues, FormikProps, useFormikContext, getIn } from "formik";
import { useTranslation } from "react-i18next";
import { useForm, FieldInterface, Button } from "@arteneo/forge";
import { Resizable } from "re-resizable";
import MonacoEditor, { MonacoEditorProps } from "react-monaco-editor";
import { CodeOutlined, SearchOutlined } from "@mui/icons-material";

interface MonacoSpecificProps {
    languageInformation?: string;
    disableActionFormat?: boolean;
    disableActionFind?: boolean;
    onChange?: (
        path: string,
        // eslint-disable-next-line
        setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void,
        event: editor.IModelContentChangedEvent,
        value: string,
        onChange: () => void,
        values: FormikValues,
        name: string
    ) => void;
    monacoEditorProps?: MonacoEditorProps;
    formLabelProps?: FormLabelProps;
    formControlProps?: FormControlProps;
}

type MonacoProps = MonacoSpecificProps & FieldInterface;

/**
 * Documentation of Monaco:
 * https://github.com/react-monaco-editor/react-monaco-editor
 * https://microsoft.github.io/monaco-editor/api/modules/monaco.html
 */
const Monaco = ({
    languageInformation,
    disableActionFormat = false,
    disableActionFind = false,
    onChange,
    monacoEditorProps,
    formLabelProps,
    formControlProps,
    // eslint-disable-next-line
    validate: fieldValidate = (value: any, required: boolean) => {
        if (required && !Yup.string().required().isValidSync(value)) {
            return "validation.required";
        }

        return undefined;
    },
    ...field
}: MonacoProps) => {
    const { t } = useTranslation();
    const {
        values,
        touched,
        errors,
        submitCount,
        setFieldValue,
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
    const monacoRef = React.useRef(null);

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

    const defaultOnChange = (value: string) => {
        setFieldValue(path, value);
    };

    const callableOnChange = (value: string, event: editor.IModelContentChangedEvent) => {
        if (onChange) {
            onChange(path, setFieldValue, event, value, () => defaultOnChange(value), values, name);
            return;
        }

        defaultOnChange(value);
    };

    const internalFormControlProps: FormControlProps = {
        error: hasError,
    };
    const mergedFormControlProps = Object.assign(internalFormControlProps, formControlProps);

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

    const internalMonacoEditorProps: MonacoEditorProps = {
        theme: "vs-light",
        options: {
            wordWrap: "on",
            readOnly: disabled,
            automaticLayout: true,
            snippetSuggestions: "none",
            codeLens: false,
            contextmenu: false,
            // Config can be a mix of JSON and Twig syntax.
            // We are formatting as JSON and Twig syntax will be broken. Disable formatting on paste.
            // plain formatting is unaffected as it does not do anything.
            // User can still format manually using a button.
            formatOnPaste: false,
            inlayHints: {
                enabled: "off",
            },
            inlineSuggest: {
                enabled: false,
            },
            lightbulb: {
                enabled: editor.ShowLightbulbIconMode.Off,
            },
            parameterHints: {
                enabled: false,
            },
            quickSuggestions: false,
            minimap: {
                enabled: false,
            },
        },
        onChange: callableOnChange,
        value: getIn(values, path, ""),
    };
    const mergedMonacoEditorProps = Object.assign(internalMonacoEditorProps, monacoEditorProps);

    const hasActionsBar = languageInformation || !disableActionFind || !disableActionFormat;

    return (
        <FormControl {...mergedFormControlProps}>
            {label && <FormLabel {...mergedFormLabelProps}>{label}</FormLabel>}
            <Box
                {...{
                    // contentEditable: true enables copy/paste from default context menu on right click (might not work in all browsers)
                    // suppressContentEditableWarning: true prevents warning from React that user can change content directly in browser as we are handling it with MonacoEditor
                    contentEditable: true,
                    suppressContentEditableWarning: true,
                    sx: {
                        overflow: "hidden",
                        borderRadius: 1,
                        borderWidth: 1,
                        borderStyle: "solid",
                        borderColor: "grey.400",
                    },
                }}
            >
                {hasActionsBar && (
                    <Box
                        {...{
                            sx: {
                                display: "flex",
                                gap: 1,
                                justifyContent: "flex-end",
                                p: 0.5,
                                borderBottomWidth: 1,
                                borderBottomStyle: "solid",
                                borderBottomColor: "grey.400",
                            },
                        }}
                    >
                        {languageInformation && (
                            <Chip
                                {...{
                                    label: languageInformation,
                                    variant: "outlined",
                                }}
                            />
                        )}
                        {!disableActionFind && (
                            <Button
                                {...{
                                    startIcon: <SearchOutlined />,
                                    size: "small",
                                    variant: "contained",
                                    color: "info",
                                    onClick: () => {
                                        // eslint-disable-next-line
                                        (monacoRef.current as any).editor.getAction("actions.find").run();
                                    },
                                }}
                            >
                                {t("monaco.action.find")}
                            </Button>
                        )}
                        {!disableActionFormat && (
                            <Button
                                {...{
                                    startIcon: <CodeOutlined />,
                                    size: "small",
                                    variant: "contained",
                                    color: "info",
                                    onClick: () => {
                                        // eslint-disable-next-line
                                        (monacoRef.current as any).editor
                                            .getAction("editor.action.formatDocument")
                                            .run();
                                    },
                                }}
                            >
                                {t("monaco.action.format")}
                            </Button>
                        )}
                    </Box>
                )}
                <Resizable
                    {...{
                        defaultSize: {
                            width: "100%",
                            height: 300,
                        },
                        enable: {
                            top: false,
                            right: false,
                            bottom: true,
                            left: false,
                            topRight: false,
                            bottomRight: false,
                            bottomLeft: false,
                            topLeft: false,
                        },
                    }}
                >
                    <MonacoEditor ref={monacoRef} {...mergedMonacoEditorProps} />
                </Resizable>
            </Box>
            {helperText && <FormHelperText>{helperText}</FormHelperText>}
        </FormControl>
    );
};

export default Monaco;
export { MonacoProps, MonacoSpecificProps };
