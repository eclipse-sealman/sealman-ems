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
import { TranslateVariablesInterface } from "@arteneo/forge";
import { Alert } from "@mui/material";
import { useTranslation } from "react-i18next";
import Display, { DisplayProps, DisplayRowsInterface } from "~app/components/Display/Display";
import { DisplayRowTitleProps } from "~app/components/Display/DisplayRowTitle";
import VariableInterface from "~app/definitions/VariableInterface";
import VariablePreColumn from "~app/components/Table/columns/VariablePreColumn";

interface DisplayVariablesProps extends Omit<DisplayProps, "result" | "rows"> {
    variables: VariableInterface[];
    emptyLabel?: string;
    emptyLabelVariables?: TranslateVariablesInterface;
}

const DisplayVariables = ({
    variables,
    emptyLabel = "displayVariables.emptyVariables",
    emptyLabelVariables = {},
    ...displayProps
}: DisplayVariablesProps) => {
    const { t } = useTranslation();

    if (variables.length === 0) {
        return <Alert severity="info">{t(emptyLabel, emptyLabelVariables)}</Alert>;
    }

    const rows: DisplayRowsInterface = {};
    variables.forEach(({ name, variableValue }) => {
        rows[name] = <VariablePreColumn {...{ content: variableValue }} />;
    });

    const getTitleProps = (rowKey: string): DisplayRowTitleProps => {
        const variable = variables.find((variable) => variable.name === rowKey);

        return {
            title: "displayVariables.variableTitle",
            titleVariables: {
                name: variable?.name,
            },
        };
    };

    return (
        <Display
            {...{
                result: variables,
                rows,
                getTitleProps,
                ...displayProps,
            }}
        />
    );
};

export default DisplayVariables;
export { DisplayVariablesProps };
