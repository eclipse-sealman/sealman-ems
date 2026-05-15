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
import { CustomDataValuesInterface } from "~app/entities/Common/definitions";
import useEndpoint from "~app/hooks/useEndpoint";

interface VariableValueInterface {
    name: string;
    variableValue?: string;
}

interface DeviceCustomDataValuesDisplayProps {
    device: DeviceInterface;
}

const DeviceCustomDataValuesDisplay = ({ device }: DeviceCustomDataValuesDisplayProps) => {
    const { object: customDataValues, loading } = useEndpoint<CustomDataValuesInterface[]>(
        "/device/" + device.id + "/custom/data/values"
    );

    if (loading) {
        return (
            <Box {...{ sx: { display: "flex", justifyContent: "center" } }}>
                <CircularProgress {...{ size: 32 }} />
            </Box>
        );
    }

    const variables: VariableValueInterface[] = (customDataValues ?? []).map(({ name, value }) => ({
        name: name,
        variableValue: value,
    }));

    return <DisplayVariables {...{ variables, collapseRowsAbove: 12 }} />;
};

export default DeviceCustomDataValuesDisplay;
export { DeviceCustomDataValuesDisplayProps, VariableValueInterface };
