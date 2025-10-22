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
import { AXIOS_CANCELLED_UNMOUNTED, useHandleCatch } from "@arteneo/forge";
import { Box, CircularProgress } from "@mui/material";
import axios from "axios";
import { DeviceInterface } from "~app/entities/Device/definitions";
import DisplayVariables from "~app/components/Display/DisplayVariables";
import VariableInterface from "~app/definitions/VariableInterface";

interface DevicePredefinedVariablesDisplayProps {
    device: DeviceInterface;
}

const DevicePredefinedVariablesDisplay = ({ device }: DevicePredefinedVariablesDisplayProps) => {
    const handleCatch = useHandleCatch();

    const [variables, setVariables] = React.useState<VariableInterface[]>([]);
    const [loading, setLoading] = React.useState(true);

    React.useEffect(() => load(), []);

    const load = () => {
        setLoading(true);

        const axiosSource = axios.CancelToken.source();

        axios
            .get("/device/" + device.id + "/predefined/variables")
            .then((response) => {
                const responseVariables = response.data;
                const variables = Object.keys(responseVariables).map((variableName) => ({
                    name: variableName,
                    variableValue: responseVariables[variableName],
                }));

                setVariables(variables);
                setLoading(false);
            })
            .catch((error) => {
                setLoading(false);
                handleCatch(error);
            });

        return () => {
            axiosSource.cancel(AXIOS_CANCELLED_UNMOUNTED);
        };
    };

    if (loading) {
        return (
            <Box {...{ sx: { display: "flex", justifyContent: "center" } }}>
                <CircularProgress {...{ size: 32 }} />
            </Box>
        );
    }

    return <DisplayVariables {...{ variables, collapseRowsAbove: 12 }} />;
};

export default DevicePredefinedVariablesDisplay;
export { DevicePredefinedVariablesDisplayProps };
