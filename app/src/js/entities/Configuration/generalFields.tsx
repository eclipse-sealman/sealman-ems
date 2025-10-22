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
import { getFields, RadioEnum, RadioFalseTrue, Text } from "@arteneo/forge";
import { routerIdentifier } from "~app/entities/Configuration/enums";
import { showAndRequireOnTrue } from "~app/utilities/fields";

const fields = {
    routerIdentifier: <RadioEnum {...{ required: true, enum: routerIdentifier, help: true }} />,
    configGeneratorPhp: <RadioFalseTrue {...{ required: true, help: true }} />,
    configGeneratorTwig: <RadioFalseTrue {...{ required: true }} />,
    diskUsageAlarm: <Text {...{ required: true }} />,
    autoRemoveBackupsAfter: <Text {...{ required: true, help: true }} />,
    failedLoginAttemptsEnabled: <RadioFalseTrue {...{ required: true }} />,
    failedLoginAttemptsLimit: <Text {...{ ...showAndRequireOnTrue("failedLoginAttemptsEnabled"), help: true }} />,
    failedLoginAttemptsDisablingDuration: (
        <Text {...{ ...showAndRequireOnTrue("failedLoginAttemptsEnabled"), help: true }} />
    ),
    passwordExpireDays: <Text {...{ required: true, help: true }} />,
    passwordBlockReuseOldPasswordCount: <Text {...{ required: true, help: true }} />,
    passwordMinimumLength: <Text {...{ required: true, help: true }} />,
    passwordDigitRequired: <RadioFalseTrue {...{ required: true, help: true }} />,
    passwordBigSmallCharRequired: <RadioFalseTrue {...{ required: true, help: true }} />,
    passwordSpecialCharRequired: <RadioFalseTrue {...{ required: true, help: true }} />,
};

export default getFields(fields);
