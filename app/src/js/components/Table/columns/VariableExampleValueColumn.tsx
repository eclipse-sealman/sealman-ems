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
import { ColumnPathInterface, Optional } from "@arteneo/forge";
import VariablePre, { VariablePreProps } from "~app/components/Common/VariablePre";

type VariableExampleValueColumnProps = Optional<VariablePreProps, "content"> & ColumnPathInterface;

const VariableExampleValueColumn = ({ result, columnName, content, ...props }: VariableExampleValueColumnProps) => {
    if (typeof columnName === "undefined") {
        throw new Error("VariableExampleValueColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("VariableExampleValueColumn component: Missing required result prop");
    }

    return (
        <VariablePre
            {...{
                disableCopyToClipBoard: true,
                helpTooltip: "help.exampleDeviceSecretValue",
                helpTooltipVariables: { value: content },
                ...props,
                content: "",
            }}
        />
    );
};

export default VariableExampleValueColumn;
export { VariableExampleValueColumnProps };
