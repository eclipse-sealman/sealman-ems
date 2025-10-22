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

import { FormikValues } from "formik";
import { FieldsInterface, filterInitialValues, transformInitialValues } from "@arteneo/forge";
import { AxiosResponse } from "axios";
import { CommunicationProcedureRequirements } from "~app/entities/DeviceType/definitions";
import _ from "lodash";

/**
 * DeviceType forms transforms initial data for easier frontend/formik usage and after submit transforms formik data to backend form requirements.
 * Non standard process is used due to multiple conditions and relationships between fields and system state (communication procedure license)
 * In this file helper methods are placed because they are shared between multiple deviceType forms
 */

const isFieldUnused = (requirements: CommunicationProcedureRequirements, name: string): boolean => {
    if (requirements.communicationProcedureRequirementsRequired.includes(name)) {
        return false;
    }

    if (requirements.communicationProcedureRequirementsOptional.includes(name)) {
        return false;
    }

    return true;
};

const communicationProcedureRequirementArray = [
    "hasFirmware1",
    "hasFirmware2",
    "hasFirmware3",
    "hasConfig1",
    "hasConfig2",
    "hasConfig3",
    "hasAlwaysReinstallConfig1",
    "hasAlwaysReinstallConfig2",
    "hasAlwaysReinstallConfig3",
    "hasCertificates",
    "hasVpn",
    "hasEndpointDevices",
    "hasTemplates",
    "hasMasquerade",
    "hasGsm",
    "hasRequestDiagnose",
    "hasRequestConfig",
    "hasDeviceCommands",
    "hasVariables",
    "hasDeviceToNetworkConnection",
];

const hasNoneCommunicationProcedure = (communicationProcedureName?: string) => {
    if (communicationProcedureName === undefined) {
        return false;
    }

    if (
        communicationProcedureName === "none" ||
        communicationProcedureName === "noneScep" ||
        communicationProcedureName === "noneVpn"
    ) {
        return true;
    }
    return false;
};

const isCertificateTypeCredentialUsed = (values: FormikValues): boolean => {
    return values?.["authenticationMethod"] === "x509";
};

const isCredentialsSourceUsed = (values: FormikValues): boolean => {
    return values?.["authenticationMethod"] === "basic" || values?.["authenticationMethod"] === "digest";
};

const isDeviceSecretCredentialUsed = (values: FormikValues): boolean => {
    if (isCredentialsSourceUsed(values)) {
        return (
            values?.["credentialsSource"] === "secret" ||
            values?.["credentialsSource"] === "both" ||
            values?.["credentialsSource"] === "userIfSecretMissing"
        );
    } else {
        return false;
    }
};

// Method prepares submit values to requirements of backend form (depending on form values)
const processDeviceTypeSubmitValues = (
    values: FormikValues,
    communicationProcedureName: string,
    requirements: CommunicationProcedureRequirements
): FormikValues => {
    //Values array is cloned because, if form will return validation errors, some fields might be cleared
    const _values = _.cloneDeep(values);

    delete _values.masquerades;

    communicationProcedureRequirementArray.forEach((name) => {
        if (isFieldUnused(requirements, name)) {
            delete _values[name];
        }
    });

    if (!isCertificateTypeCredentialUsed(_values)) {
        delete _values.deviceTypeCertificateTypeCredential;
    } else {
        if (typeof _values.deviceTypeCertificateTypeCredential === "object") {
            if (typeof _values.deviceTypeCertificateType.certificateType === "object") {
                _values.deviceTypeCertificateTypeCredential = _values.deviceTypeCertificateType.certificateType.id;
            } else {
                _values.deviceTypeCertificateTypeCredential = _values.deviceTypeCertificateType.certificateType;
            }
        }
    }

    if (!isDeviceSecretCredentialUsed(_values)) {
        delete _values.deviceTypeSecretCredential;
    }

    if (!isCredentialsSourceUsed(_values)) {
        delete _values.credentialsSource;
    }

    if (hasNoneCommunicationProcedure(communicationProcedureName)) {
        delete _values.enableConfigLogs;
        delete _values.routePrefix;
        delete _values.authenticationMethod;
        delete _values.enableConnectionAggregation;
    }

    if (!_values.hasVariables) {
        delete _values.hasCertificates;
    }

    if (!_values.hasCertificates) {
        delete _values.certificateTypes;
    }

    let deviceVpnCertificate = false;
    if (_values.certificateTypes) {
        _values.certificateTypes.forEach((deviceTypeCertificateType: FormikValues, index: number | string) => {
            if (!deviceTypeCertificateType.hasCertificateType) {
                delete _values.certificateTypes[index];
                return;
            }

            delete _values.certificateTypes[index].hasCertificateType;

            if (!deviceTypeCertificateType.enableCertificatesAutoRenew) {
                delete _values.certificateTypes[index].certificatesAutoRenewDaysBefore;
            }

            if (!deviceTypeCertificateType.enableSubjectAltName) {
                delete _values.certificateTypes[index].subjectAltNameType;
                delete _values.certificateTypes[index].subjectAltNameValue;
            }

            if (typeof deviceTypeCertificateType.certificateType === "object") {
                _values.certificateTypes[index].certificateType = deviceTypeCertificateType.certificateType.id;
            }

            //checking if deviceVpnCertificate is available for further conditions
            if (deviceTypeCertificateType?.certificateType === requirements?.deviceVpnCertificateType?.id) {
                deviceVpnCertificate = true;
            }
        });
        _values.certificateTypes = _values.certificateTypes.filter(
            (deviceTypeCertificateType: FormikValues) => deviceTypeCertificateType !== null
        );
    }

    if (!deviceVpnCertificate) {
        delete _values.hasVpn;
        delete _values.hasDeviceToNetworkConnection;
    }

    if (!_values.hasVpn) {
        delete _values.hasEndpointDevices;
        delete _values.hasMasquerade;
    }

    if (!_values.hasFirmware1 && !_values.hasFirmware2 && !_values.hasFirmware3) {
        delete _values.enableFirmwareMinRsrp;
    }

    if (!_values.hasConfig1 && !_values.hasConfig2 && !_values.hasConfig3) {
        delete _values.enableConfigMinRsrp;
    }

    if (!_values.hasFirmware1) {
        delete _values.nameFirmware1;
        delete _values.customUrlFirmware1;
    }

    if (!_values.hasFirmware2) {
        delete _values.nameFirmware2;
        delete _values.customUrlFirmware2;
    }

    if (!_values.hasFirmware3) {
        delete _values.nameFirmware3;
        delete _values.customUrlFirmware3;
    }

    if (!_values.hasConfig1) {
        delete _values.hasAlwaysReinstallConfig1;
        delete _values.nameConfig1;
        delete _values.formatConfig1;
    }

    if (!_values.hasConfig2) {
        delete _values.hasAlwaysReinstallConfig2;
        delete _values.nameConfig2;
        delete _values.formatConfig2;
    }

    if (!_values.hasConfig3) {
        delete _values.hasAlwaysReinstallConfig3;
        delete _values.nameConfig3;
        delete _values.formatConfig3;
    }

    if (!_values.hasEndpointDevices) {
        delete _values.virtualSubnetCidr;
    }

    if (!_values.hasMasquerade) {
        delete _values.masqueradeType;
    }

    if (!_values.hasDeviceCommands) {
        delete _values.deviceCommandMaxRetries;
        delete _values.deviceCommandExpireDuration;
    }

    if (!_values.enableConnectionAggregation) {
        delete _values.connectionAggregationPeriod;
    }

    if (!_values.enableFirmwareMinRsrp) {
        delete _values.firmwareMinRsrp;
    }

    if (!_values.enableConfigMinRsrp) {
        delete _values.configMinRsrp;
    }

    return _values;
};

// Method prepares submit values to requirements of backend limited edit form (depending on form values)
const processDeviceTypeLimitedSubmitValues = (
    values: FormikValues,
    hasCertificates: boolean,
    hasEndpointDevices: boolean,
    hasMasquerade: boolean,
    hasDeviceCommands: boolean,
    hasConfig: boolean,
    hasFirmware: boolean,
    hasNoneCommunicationProcedure: boolean
): FormikValues => {
    //Values array is cloned because, if form will return validation errors, some fields might be cleared
    const _values = _.cloneDeep(values);

    delete _values.hasVpn;
    delete _values.hasCertificates;
    delete _values.hasEndpointDevices;
    delete _values.hasDeviceCommands;
    delete _values.hasMasquerade;
    delete _values.masquerades;

    if (!isCertificateTypeCredentialUsed(_values)) {
        delete _values.deviceTypeCertificateTypeCredential;
    } else {
        if (typeof _values.deviceTypeCertificateTypeCredential === "object") {
            if (typeof _values.deviceTypeCertificateType.certificateType === "object") {
                _values.deviceTypeCertificateTypeCredential = _values.deviceTypeCertificateType.certificateType.id;
            } else {
                _values.deviceTypeCertificateTypeCredential = _values.deviceTypeCertificateType.certificateType;
            }
        }
    }

    if (!isDeviceSecretCredentialUsed(_values)) {
        delete _values.deviceTypeSecretCredential;
    }

    if (!isCredentialsSourceUsed(_values)) {
        delete _values.credentialsSource;
    }

    if (hasNoneCommunicationProcedure) {
        delete _values.enableConfigLogs;
        delete _values.enableConnectionAggregation;
        delete _values.authenticationMethod;
    }

    if (!hasFirmware) {
        delete _values.enableFirmwareMinRsrp;
    }

    if (!hasConfig) {
        delete _values.enableConfigMinRsrp;
    }

    if (!hasEndpointDevices) {
        delete _values.virtualSubnetCidr;
    }

    if (!hasMasquerade) {
        delete _values.masqueradeType;
    }

    if (!hasDeviceCommands) {
        delete _values.deviceCommandMaxRetries;
        delete _values.deviceCommandExpireDuration;
    }

    if (!_values.enableConnectionAggregation) {
        delete _values.connectionAggregationPeriod;
    }

    if (!_values.enableFirmwareMinRsrp) {
        delete _values.firmwareMinRsrp;
    }

    if (!_values.enableConfigMinRsrp) {
        delete _values.configMinRsrp;
    }

    if (!hasCertificates) {
        delete _values.certificateTypes;
    }

    if (_values.certificateTypes) {
        _values.certificateTypes.forEach((deviceTypeCertificateType: FormikValues, index: number | string) => {
            if (!deviceTypeCertificateType.hasCertificateType) {
                delete _values.certificateTypes[index];
                return;
            }

            delete _values.certificateTypes[index].hasCertificateType;

            if (!deviceTypeCertificateType.enableCertificatesAutoRenew) {
                delete _values.certificateTypes[index].certificatesAutoRenewDaysBefore;
            }

            if (!deviceTypeCertificateType.enableSubjectAltName) {
                delete _values.certificateTypes[index].subjectAltNameType;
                delete _values.certificateTypes[index].subjectAltNameValue;
            }

            if (typeof deviceTypeCertificateType.certificateType === "object") {
                _values.certificateTypes[index].certificateType = deviceTypeCertificateType.certificateType.id;
            }
        });
        _values.certificateTypes = _values.certificateTypes.filter(
            (deviceTypeCertificateType: FormikValues) => deviceTypeCertificateType !== null
        );
    }

    return _values;
};

// Method transforms deviceType certificateTypes into formik friendly form
const processDeviceTypeCertificateTypesInitialValues = (
    fields: FieldsInterface,
    requirements: CommunicationProcedureRequirements,
    initialValues?: FormikValues,
    response?: AxiosResponse
): FormikValues => {
    const values = transformInitialValues(fields, filterInitialValues(fields, initialValues, response?.data));
    const fieldName = "certificateTypes";
    if (!values[fieldName]) {
        return values;
    }
    if (!Array.isArray(values[fieldName])) {
        return values;
    }

    //hasCertificateType added to initialized fields
    values[fieldName].forEach((row: FormikValues) => {
        row["hasCertificateType"] = true;
    });

    const collectionOptionalValues = requirements.communicationProcedureCertificateCategoryOptional.map(
        (certificateType) => {
            const row = values[fieldName].find((row: FormikValues) => row?.certificateType?.id === certificateType.id);

            if (row) {
                return row;
            }

            return {
                certificateType: certificateType.id,
                hasCertificateType: false,
                certificateEncoding: "hex",
            };
        }
    );

    const collectionRequiredValues = requirements.communicationProcedureCertificateCategoryRequired.map(
        (certificateType) => {
            const row = values[fieldName].find((row: FormikValues) => row?.certificateType?.id === certificateType.id);
            if (row) {
                row.hasCertificateType = true;
                return row;
            }

            return {
                hasCertificateType: true,
                certificateType: certificateType.id,
                certificateEncoding: "hex",
            };
        }
    );

    values[fieldName] = collectionRequiredValues.concat(collectionOptionalValues);

    return values;
};

export {
    processDeviceTypeSubmitValues,
    processDeviceTypeLimitedSubmitValues,
    hasNoneCommunicationProcedure,
    processDeviceTypeCertificateTypesInitialValues,
    isCredentialsSourceUsed,
    isDeviceSecretCredentialUsed,
    isCertificateTypeCredentialUsed,
};
