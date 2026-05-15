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
import { Text, getFields, RadioEnum, FieldsInterface, SelectApi } from "@arteneo/forge";
import { sourceType } from "~app/entities/Firmware/enums";
import FirmwareFilepath from "~app/components/Form/fields/FirmwareFilepath";
import { showAndRequireOnEqual } from "~app/utilities/fields";

const composeGetFields = (deviceTypeId: number) => {
    const fields: FieldsInterface = {
        hardware: <SelectApi {...{ required: true, endpoint: "/options/hardwares/" + deviceTypeId }} />,
        sourceType: <RadioEnum {...{ required: true, enum: sourceType }} />,
        externalUrl: <Text {...{ ...showAndRequireOnEqual("sourceType", "externalUrl") }} />,
        md5: <Text {...{ ...showAndRequireOnEqual("sourceType", "externalUrl") }} />,
        filepath: <FirmwareFilepath {...{ ...showAndRequireOnEqual("sourceType", "upload") }} />,
    };

    return getFields(fields);
};

export default composeGetFields;
