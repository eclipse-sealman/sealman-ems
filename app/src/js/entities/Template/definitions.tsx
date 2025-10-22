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
import EntityInterface from "~app/definitions/EntityInterface";
import { DeviceConfigurationTypeInterface } from "~app/entities/DeviceType/definitions";

interface TemplateInterface extends EntityDenyInterface {
    name: string;
    deviceType: DeviceConfigurationTypeInterface;
    stagingTemplate: EntityInterface;
    productionTemplate: EntityInterface;
}

export { TemplateInterface };
