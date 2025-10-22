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

import EntityDenyInterface from "~app/definitions/EntityDenyInterface";
import { DeviceTypeInterface } from "~app/entities/DeviceType/definitions";
import { GeneratorType } from "~app/entities/Config/enums";
import { FeatureType } from "~app/enums/Feature";

interface ConfigInterface extends EntityDenyInterface {
    name: string;
    content: string;
    uuid: string;
    deviceType: DeviceTypeInterface;
    feature: FeatureType;
    generator: GeneratorType;
}

export { ConfigInterface };
