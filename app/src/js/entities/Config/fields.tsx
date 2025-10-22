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
import { getConfigGeneratorEnum } from "~app/entities/Config/utilities";
import MonacoJson from "~app/components/Form/fields/MonacoJson";
import MonacoPlain from "~app/components/Form/fields/MonacoPlain";
import { FormatConfigType } from "~app/entities/DeviceType/enums";
import ConfigReinstall from "~app/components/Form/fields/ConfigReinstall";
import { FeatureType } from "~app/enums/Feature";

const composeGetFields = (
    formatConfig: FormatConfigType,
    hasAlwaysReinstallConfig: boolean,
    configGeneratorPhp: boolean,
    configGeneratorTwig: boolean,
    feature?: FeatureType,
    configId?: string | number
) => {
    let content: undefined | React.ReactElement = undefined;

    switch (formatConfig) {
        case "json":
            content = <MonacoJson {...{ required: true }} />;
            break;
        case "plain":
            content = <MonacoPlain {...{ required: true }} />;
            break;
    }

    const fields: FieldsInterface = {
        name: <Text {...{ required: true }} />,
        generator: (
            <RadioEnum {...{ required: true, enum: getConfigGeneratorEnum(configGeneratorPhp, configGeneratorTwig) }} />
        ),
        content,
    };

    if (feature && configId && !hasAlwaysReinstallConfig) {
        fields.reinstallConfig = <ConfigReinstall {...{ configId, feature, required: true }} />;
    }

    return getFields(fields);
};

export default composeGetFields;
