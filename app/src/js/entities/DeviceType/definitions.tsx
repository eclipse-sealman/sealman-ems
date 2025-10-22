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

import { OptionInterface } from "@arteneo/forge";
import EntityInterface from "~app/definitions/EntityInterface";
import { CommunicationProcedureType, FormatConfigType } from "~app/entities/DeviceType/enums";
import { FieldRequirementType } from "~app/enums/FieldRequirement";
import { MasqueradeTypeType } from "~app/enums/MasqueradeType";

interface DeviceTypeOptionInterface extends OptionInterface {
    icon?: string;
    color?: string;
}

type DeviceTypeOptionsType = DeviceTypeOptionInterface[];

interface DeviceConfigurationTypeInterface extends EntityInterface {
    name: string;
    hasConfig1: boolean;
    hasAlwaysReinstallConfig1: boolean;
    nameConfig1?: string;
    formatConfig1?: FormatConfigType;
    hasConfig2: boolean;
    hasAlwaysReinstallConfig2: boolean;
    nameConfig2?: string;
    formatConfig2?: FormatConfigType;
    hasConfig3: boolean;
    hasAlwaysReinstallConfig3: boolean;
    nameConfig3?: string;
    formatConfig3?: FormatConfigType;
    hasFirmware1: boolean;
    nameFirmware1?: string;
    hasFirmware2: boolean;
    nameFirmware2?: string;
    hasFirmware3: boolean;
    nameFirmware3?: string;
    isVpnAvailable: boolean;
    isEndpointDevicesAvailable: boolean;
    isMasqueradeAvailable: boolean;
    hasVariables: boolean;
    hasRequestConfig: boolean;
    hasRequestDiagnose: boolean;
    hasTemplates: boolean;
    hasDeviceCommands: boolean;
    fieldSerialNumber: FieldRequirementType;
    fieldImsi: FieldRequirementType;
    fieldModel: FieldRequirementType;
    fieldRegistrationId: FieldRequirementType;
    fieldEndorsementKey: FieldRequirementType;
    fieldHardwareVersion: FieldRequirementType;
    hasGsm: boolean;
    hasCertificates: boolean;
    enableConnectionAggregation: boolean;
    connectionAggregationPeriod: number;
    virtualSubnetCidr: number;
    masqueradeType: MasqueradeTypeType;
    communicationProcedure: CommunicationProcedureType;
    certificateTypes: DeviceTypeCertificateType[];
}

interface DeviceTypeCertificateType extends EntityInterface {
    certificateType: EntityInterface;
    isCertificateTypeAvailable: boolean;
}

interface DeviceTypeInterface extends DeviceConfigurationTypeInterface {
    deviceName: string;
}

interface CommunicationProcedureRequirements {
    communicationProcedureRequirementsRequired: string[];
    communicationProcedureRequirementsOptional: string[];
    communicationProcedureCertificateCategoryRequired: OptionInterface[];
    communicationProcedureCertificateCategoryOptional: OptionInterface[];
    deviceVpnCertificateType?: OptionInterface;
}

export {
    DeviceTypeOptionsType,
    DeviceTypeCertificateType,
    DeviceTypeOptionInterface,
    DeviceTypeInterface,
    DeviceConfigurationTypeInterface,
    CommunicationProcedureRequirements,
};
