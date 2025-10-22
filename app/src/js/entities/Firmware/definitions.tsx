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

import EntityInterface from "~app/definitions/EntityInterface";
import { DeviceTypeInterface } from "~app/entities/DeviceType/definitions";
import { SourceTypeType } from "~app/entities/Firmware/enums";
import { FeatureType } from "~app/enums/Feature";

interface FirmwareInterface extends EntityInterface {
    name: string;
    md5: string;
    filename: string;
    uuid: string;
    version: string;
    deviceType: DeviceTypeInterface;
    feature: FeatureType;
    sourceType: SourceTypeType;
    filepath?: string;
    externalUrl?: string;
}

export { FirmwareInterface };
