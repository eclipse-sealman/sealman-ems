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
import EntityVariableInterface from "~app/definitions/EntityVariableInterface";
import { UseableCertificateEntityInterface } from "~app/entities/Common/definitions";
import { DeviceEndpointDeviceInterface } from "~app/entities/DeviceEndpointDevice/definitions";
import { DeviceConfigurationTypeInterface } from "~app/entities/DeviceType/definitions";
import { CommunicationProcedureType } from "~app/entities/DeviceType/enums";
import { MasqueradeTypeType } from "~app/enums/MasqueradeType";

interface DeviceMasqueradeInterface extends EntityInterface {
    subnet: string;
}

interface DeviceInterface extends EntityDenyInterface, UseableCertificateEntityInterface {
    deviceType: DeviceConfigurationTypeInterface;
    identifier: string;
    name: string;
    description?: string;
    uuid?: string;
    enabled: boolean;
    staging: boolean;
    serialNumber?: string;
    registrationId?: string;
    endorsementKey?: string;
    hardwareVersion?: string;
    model?: string;
    commandRetryCount?: string;
    lastCommandCritical?: boolean;
    connectionAmount?: number;
    connectionAmountFrom?: string;
    reinstallFirmware1?: boolean;
    reinstallFirmware2?: boolean;
    reinstallFirmware3?: boolean;
    reinstallConfig1?: boolean;
    reinstallConfig2?: boolean;
    reinstallConfig3?: boolean;
    requestDiagnoseData?: boolean;
    requestConfigData?: boolean;
    communicationProcedure?: CommunicationProcedureType;
    virtualSubnetCidr?: number;
    virtualSubnetIpSortable?: number;
    masqueradeType?: MasqueradeTypeType;
    masquerades: DeviceMasqueradeInterface[];
    accessTags: EntityInterface[];
    template: EntityInterface;
    endpointDevices: DeviceEndpointDeviceInterface[];
    variables: EntityVariableInterface[];
    vpnConnections: EntityInterface[];
    hasDeviceSecrets?: boolean;
    updatedAt?: string;
    updatedBy?: EntityInterface;
    createdAt?: string;
    createdBy?: EntityInterface;
}

export { DeviceMasqueradeInterface, DeviceInterface };
