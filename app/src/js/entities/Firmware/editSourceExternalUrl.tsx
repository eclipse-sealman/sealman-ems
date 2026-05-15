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
import { FieldsInterface, SelectApi, Text, getFields } from "@arteneo/forge";
import { getFirmwareSchema } from "~app/entities/Firmware/utilities";
import { DeviceConfigurationTypeInterface } from "~app/entities/DeviceType/definitions";
import { FeatureType } from "~app/enums/Feature";

const composeGetFields = (
    deviceType: DeviceConfigurationTypeInterface,
    feature: FeatureType,
    allowEditRequiredFirmware: boolean,
    firmwareId: number
) => {
    const firmwareSchema = getFirmwareSchema(deviceType, feature);
    const isFirmwareSchemaAny = firmwareSchema === "anySchema";

    const fields: FieldsInterface = {
        name: <Text {...{ required: true }} />,
        externalUrl: <Text {...{ required: true }} />,
        md5: <Text {...{ required: true }} />,
        version: <Text {...{ disabled: true }} />,
        requiredFirmware: (
            <SelectApi
                {...{
                    endpoint: "/firmware/required/firmware/options/" + firmwareId,
                    disabled: !allowEditRequiredFirmware && !isFirmwareSchemaAny,
                    hidden: isFirmwareSchemaAny,
                    help: !allowEditRequiredFirmware ? "help.requiredFirmwareEditDisabled" : undefined,
                }}
            />
        ),
    };

    return getFields(fields);
};

export default composeGetFields;
