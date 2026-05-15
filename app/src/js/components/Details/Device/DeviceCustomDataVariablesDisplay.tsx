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
import { Box, CircularProgress } from "@mui/material";
import { DeviceInterface } from "~app/entities/Device/definitions";
import DisplayVariables from "~app/components/Display/DisplayVariables";
import useEndpoint from "~app/hooks/useEndpoint";

interface CustomDataVariableValuesInterface {
    [index: string]: string;
}

interface VariableValueInterface {
    name: string;
    variableValue?: string;
}

interface DeviceCustomDataVariablesDisplayProps {
    device: DeviceInterface;
}

const DeviceCustomDataVariablesDisplay = ({ device }: DeviceCustomDataVariablesDisplayProps) => {
    const { object: variableValues, loading } = useEndpoint<CustomDataVariableValuesInterface>(
        "/device/" + device.id + "/custom/data/variables"
    );

    if (loading) {
        return (
            <Box {...{ sx: { display: "flex", justifyContent: "center" } }}>
                <CircularProgress {...{ size: 32 }} />
            </Box>
        );
    }

    const variables = Object.keys(variableValues ?? {}).map((variableName) => ({
        name: variableName,
        variableValue: variableValues ? variableValues[variableName] : undefined,
    }));

    return <DisplayVariables {...{ variables, collapseRowsAbove: 12 }} />;
};

export default DeviceCustomDataVariablesDisplay;
export { DeviceCustomDataVariablesDisplayProps, VariableValueInterface };
