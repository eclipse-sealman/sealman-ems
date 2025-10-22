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
import { getIn, useFormikContext } from "formik";
import { OpenInNewOutlined } from "@mui/icons-material";
import { ButtonProps, Optional, OptionsType, TranslateVariablesInterface } from "@arteneo/forge";
import RadioButton, { RadioButtonProps } from "~app/components/Form/fields/RadioButton";

interface RadioDisableDocumentationProps extends Optional<RadioButtonProps, "options"> {
    buttonLink: string;
    buttonLabel: string;
    buttonLabelVariables?: TranslateVariablesInterface;
}

const RadioDisableDocumentation = ({
    buttonLink,
    buttonLabel,
    buttonLabelVariables = {},
    name,
    path,
    ...props
}: RadioDisableDocumentationProps) => {
    if (typeof name === "undefined") {
        throw new Error("Missing name prop. By default it is injected while rendering.");
    }

    const { values } = useFormikContext();

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

    const buttonProps: ButtonProps = {
        variant: "contained",
        color: "info",
        endIcon: <OpenInNewOutlined />,
        label: buttonLabel,
        labelVariables: buttonLabelVariables,
        href: buttonLink,
        target: "_blank",
        sx: {
            alignSelf: "center",
        },
    } as ButtonProps;

    const documentationDisabled = getIn(values, path ?? name, false);

    return (
        <RadioButton
            {...{
                options,
                buttonProps: documentationDisabled ? undefined : buttonProps,
                path,
                name,
                ...props,
            }}
        />
    );
};

RadioDisableDocumentation.defaultProps = {
    // eslint-disable-next-line
    transformInitialValue: (value: any) => {
        if (typeof value === "undefined") {
            return value;
        }

        // Make sure it is a boolean value
        if (value === "false") {
            return false;
        }

        return Boolean(value);
    },
};

export default RadioDisableDocumentation;
export { RadioDisableDocumentationProps };
