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
import EntityVariableInterface from "~app/definitions/EntityVariableInterface";
import { DeviceConfigurationTypeInterface } from "~app/entities/DeviceType/definitions";
import { MasqueradeTypeType } from "~app/enums/MasqueradeType";

interface TemplateVersionMasqueradeInterface extends EntityInterface {
    subnet: string;
}

interface TemplateVersionEndpointDeviceInterface extends EntityInterface {
    name: string;
    description?: string;
    physicalIp: string;
    virtualIpHostPart: string;
    accessTags: EntityInterface[];
}

interface TemplateVersionInterface extends EntityInterface {
    name: string;
    description?: string;
    deviceDescription?: string;
    virtualSubnetCidr?: number;
    config1?: EntityInterface;
    config2?: EntityInterface;
    config3?: EntityInterface;
    firmware1?: EntityInterface;
    firmware2?: EntityInterface;
    firmware3?: EntityInterface;
    masqueradeType?: MasqueradeTypeType;
    masquerades?: TemplateVersionMasqueradeInterface[];
    variables?: EntityVariableInterface[];
    endpointDevices?: TemplateVersionEndpointDeviceInterface[];
    updatedAt?: string;
    updatedBy?: EntityInterface;
    createdAt?: string;
    createdBy?: EntityInterface;
    deviceType: DeviceConfigurationTypeInterface;
    template: EntityInterface;
    accessTags: EntityInterface[];
    deviceLabels: EntityInterface[];
}

export { TemplateVersionMasqueradeInterface, TemplateVersionEndpointDeviceInterface, TemplateVersionInterface };
