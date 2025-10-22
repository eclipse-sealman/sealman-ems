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

import { Enum } from "@arteneo/forge";
import { DeviceTypeInterface } from "~app/entities/DeviceType/definitions";
import { FormatConfigType } from "~app/entities/DeviceType/enums";
import { FeatureType } from "~app/enums/Feature";

export const getHasAlwaysReinstallConfig = (deviceType: DeviceTypeInterface, feature: FeatureType) => {
    switch (feature) {
        case "1":
            return deviceType.hasAlwaysReinstallConfig1 ?? false;
        case "2":
            return deviceType.hasAlwaysReinstallConfig2 ?? false;
        case "3":
            return deviceType.hasAlwaysReinstallConfig3 ?? false;
    }
};

export const getFeatureName = (deviceType: DeviceTypeInterface, feature: FeatureType) => {
    switch (feature) {
        case "1":
            return deviceType.nameConfig1 ?? "";
        case "2":
            return deviceType.nameConfig2 ?? "";
        case "3":
            return deviceType.nameConfig3 ?? "";
    }
};

export const getFormatConfig = (deviceType: DeviceTypeInterface, feature: FeatureType): FormatConfigType => {
    switch (feature) {
        case "1":
            return deviceType.formatConfig1 ?? "plain";
        case "2":
            return deviceType.formatConfig2 ?? "plain";
        case "3":
            return deviceType.formatConfig3 ?? "plain";
    }
};

/**
 * Generate Enum class based on enabled config generators
 */
export const getConfigGeneratorEnum = (configGeneratorPhp: boolean, configGeneratorTwig: boolean): Enum => {
    const enums = [];

    if (configGeneratorPhp) {
        enums.push("php");
    }

    if (configGeneratorTwig) {
        enums.push("twig");
    }

    return new Enum(enums, "enum.config.generator.");
};

export const getDefaultConfigGenerator = (configGeneratorTwig: boolean) => {
    if (configGeneratorTwig) {
        return "twig";
    }

    return "php";
};
