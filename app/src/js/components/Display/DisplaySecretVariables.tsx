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
import { DisplayRowTitleProps } from "~app/components/Display/DisplayRowTitle";
import VariableInterface from "~app/definitions/VariableInterface";
import VariablePreColumn from "~app/components/Table/columns/VariablePreColumn";
import DisplaySecretVariablePanel, {
    DisplaySecretVariablePanelProps,
    DisplaySecretVariablePanelRowsInterface,
} from "~app/components/Display/DisplaySecretVariablePanel";

interface DisplaySecretVariablesProps extends Omit<DisplaySecretVariablePanelProps, "result" | "rows"> {
    variables: VariableInterface[];
    emptyLabel?: string;
    emptyLabelVariables?: TranslateVariablesInterface;
    variableValueComponent?: React.ReactElement;
}

const DisplaySecretVariables = ({
    variables,
    emptyLabel = "displaySecretVariables.emptyVariables",
    emptyLabelVariables = {},
    variableValueComponent = <VariablePreColumn />,
    ...displayProps
}: DisplaySecretVariablesProps) => {
    const { t } = useTranslation();

    if (variables.length === 0) {
        return <Alert severity="info">{t(emptyLabel, emptyLabelVariables)}</Alert>;
    }

    const rows: DisplaySecretVariablePanelRowsInterface = {};
    variables.forEach(({ name, variableValue }) => {
        rows[name] = React.cloneElement(variableValueComponent, {
            content: variableValue,
        });
    });

    const getTitleProps = (rowKey: string): DisplayRowTitleProps => {
        const variable = variables.find((variable) => variable.name === rowKey);

        return {
            title: "displaySecretVariables.variableTitle",
            titleVariables: {
                name: variable?.name,
            },
        };
    };

    return (
        <DisplaySecretVariablePanel
            {...{
                result: variables,
                rows,
                getTitleProps,
                ...displayProps,
            }}
        />
    );
};

export default DisplaySecretVariables;
export { DisplaySecretVariablesProps };
