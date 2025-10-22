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
import { Text, getFields, RadioEnum, FieldsInterface } from "@arteneo/forge";
import { sourceType } from "~app/entities/Firmware/enums";
import FirmwareFilepath from "~app/components/Form/fields/FirmwareFilepath";
import { showAndRequireOnEqual } from "~app/utilities/fields";
import { DeviceConfigurationTypeInterface } from "~app/entities/DeviceType/definitions";
import { CommunicationProcedureType } from "~app/entities/DeviceType/enums";

const composeGetFields = (deviceType: DeviceConfigurationTypeInterface) => {
    let enableGuessVersion = false;

    const guessVersionCommunicationProcedures: CommunicationProcedureType[] = [
        "routerOneConfig",
        "router",
        "routerDsa",
    ];
    if (guessVersionCommunicationProcedures.includes(deviceType.communicationProcedure)) {
        enableGuessVersion = true;
    }

    const fields: FieldsInterface = {
        sourceType: <RadioEnum {...{ required: true, enum: sourceType }} />,
        externalUrl: <Text {...{ ...showAndRequireOnEqual("sourceType", "externalUrl") }} />,
        md5: <Text {...{ ...showAndRequireOnEqual("sourceType", "externalUrl") }} />,
        filepath: <FirmwareFilepath {...{ enableGuessVersion, ...showAndRequireOnEqual("sourceType", "upload") }} />,
        name: <Text {...{ required: true }} />,
        version: <Text {...{ required: true }} />,
    };

    return getFields(fields);
};

export default composeGetFields;
