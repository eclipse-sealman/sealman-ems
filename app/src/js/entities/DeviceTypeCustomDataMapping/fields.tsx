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
import { Text, RadioEnum, getFields, RadioFalseTrue } from "@arteneo/forge";
import { showAndRequireOnTrue } from "~app/utilities/fields";
import { variableType } from "~app/enums/VariableType";

const fields = {
    name: <Text {...{ required: true }} />,
    path: <Text {...{ required: true }} />,
    variableEnabled: <RadioFalseTrue {...{ required: true }} />,
    type: <RadioEnum {...{ ...showAndRequireOnTrue("variableEnabled"), enum: variableType }} />,
    variableName: <Text {...{ ...showAndRequireOnTrue("variableEnabled"), help: true }} />,
};

export default getFields(fields);
