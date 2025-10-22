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

import { DeviceTypeInterface } from "~app/entities/DeviceType/definitions";
import { FeatureType } from "~app/enums/Feature";

export const getFeatureName = (deviceType: DeviceTypeInterface, feature: FeatureType) => {
    switch (feature) {
        case "1":
            return deviceType.nameFirmware1 ?? "";
        case "2":
            return deviceType.nameFirmware2 ?? "";
        case "3":
            return deviceType.nameFirmware3 ?? "";
    }
};
