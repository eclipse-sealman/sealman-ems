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
import {
    Text,
    getFields,
    Checkbox,
    RadioEnum,
    ColorPicker,
    SelectEnum,
    FieldInterface,
    FieldsInterface,
    SelectApi,
    SelectApiProps,
} from "@arteneo/forge";
import {
    altNameType,
    certificateEncoding,
    authenticationMethod,
    credentialsSource,
} from "~app/entities/DeviceType/enums";
import { collectionShowAndRequireOnTrue, collectionShowOnTrue, showAndRequireOnTrue } from "~app/utilities/fields";
import { CommunicationProcedureRequirements } from "~app/entities/DeviceType/definitions";
import SelectDeviceTypeIcon from "~app/components/Form/fields/SelectDeviceTypeIcon";
import { FormikValues } from "formik";
import MasqueradeRadioEnum from "~app/components/Form/fields/MasqueradeRadioEnum";
import { cidr } from "~app/enums/Cidr";
import CertificateTypeCollectionDeviceType from "~app/components/Form/fields/CertificateTypeCollectionDeviceType";
import EntityInterface from "~app/definitions/EntityInterface";
import DeviceTypeCertificateTypeCredentialSelect from "~app/entities/DeviceType/fields/DeviceTypeCertificateTypeCredentialSelect";
import {
    isCertificateTypeCredentialUsed,
    isCredentialsSourceUsed,
    isDeviceSecretCredentialUsed,
} from "~app/entities/DeviceType/utilities";

const getFieldWithRequiredProps = (
    requirements: CommunicationProcedureRequirements,
    requiredField?: string,
    requireDeviceVpnCertificate = false
): FieldInterface => {
    const isRequirementMet = (values: FormikValues): boolean => {
        if (requiredField) {
            if (values[requiredField] !== true) {
                return false;
            }
        }
        if (requireDeviceVpnCertificate) {
            let requirementMet = false;
            if (Array.isArray(values?.certificateTypes)) {
                values.certificateTypes.forEach((deviceTypeCertificateType: FormikValues) => {
                    if (
                        deviceTypeCertificateType?.certificateType == requirements?.deviceVpnCertificateType?.id ||
                        deviceTypeCertificateType?.certificateType?.id == requirements?.deviceVpnCertificateType?.id
                    ) {
                        if (deviceTypeCertificateType?.hasCertificateType == true) {
                            requirementMet = true;
                        }
                    }
                });
            }

            return requirementMet;
        }
        return true;
    };

    if (!requiredField && !requireDeviceVpnCertificate) {
        return {
            disabled: false,
        };
    }

    return {
        disabled: (values: FormikValues) => !isRequirementMet(values),
        disableTranslateHelp: true,
    };
};

const getFieldProps = (
    requirements: CommunicationProcedureRequirements,
    name: string,
    requiredField?: string,
    requireDeviceVpnCertificate = false
): FieldInterface => {
    if (requirements.communicationProcedureRequirementsRequired.includes(name)) {
        return {
            required: true,
            disabled: true,
        };
    }
    if (requirements.communicationProcedureRequirementsOptional.includes(name)) {
        return Object.assign(
            {
                required: false,
            },
            getFieldWithRequiredProps(requirements, requiredField, requireDeviceVpnCertificate)
        );
    }
    return {
        required: false,
        disabled: true,
        hidden: true,
    };
};

const getIsEnableField = (requirements: CommunicationProcedureRequirements, name: string): boolean => {
    for (let i = 1; i <= 3; i++) {
        if (requirements.communicationProcedureRequirementsRequired.includes(name + (i + ""))) {
            return true;
        }
        if (requirements.communicationProcedureRequirementsOptional.includes(name + (i + ""))) {
            return true;
        }
    }
    return false;
};

const getEnableFieldProps = (requirements: CommunicationProcedureRequirements, name: string): FieldInterface => {
    if (getIsEnableField(requirements, name)) {
        return {
            required: false,
            disabled: false,
            hidden: false,
        };
    }
    return {
        required: false,
        disabled: true,
        hidden: true,
    };
};

const getCredentialsSourceProps = (): FieldInterface => {
    return {
        hidden: (values: FormikValues) => (isCredentialsSourceUsed(values) ? false : true),
        required: (values: FormikValues) => (isCredentialsSourceUsed(values) ? true : false),
        disabled: false,
        help: true,
    };
};

const getDeviceTypeSecretCredentialProps = (deviceTypeId: string | number): SelectApiProps => {
    return {
        hidden: (values: FormikValues) => (isDeviceSecretCredentialUsed(values) ? false : true),
        required: (values: FormikValues) => (isDeviceSecretCredentialUsed(values) ? true : false),
        help: true,
        endpoint: "/options/devicetypesecrets/" + deviceTypeId,
    };
};

const getDeviceTypeCertificateTypeCredentialProps = (): FieldInterface => {
    return {
        hidden: (values: FormikValues) => (isCertificateTypeCredentialUsed(values) ? false : true),
        required: (values: FormikValues) => (isCertificateTypeCredentialUsed(values) ? true : false),
        help: true,
    };
};

const composeGetFields = (
    requirements: CommunicationProcedureRequirements,
    usedCertificateTypes: EntityInterface[],
    initialValues: FormikValues
) => {
    const certificateTypes = requirements.communicationProcedureCertificateCategoryRequired.concat(
        requirements.communicationProcedureCertificateCategoryOptional
    );

    let requiredCertificateTypes =
        requirements.communicationProcedureCertificateCategoryRequired.concat(usedCertificateTypes);

    //Specific hasVpn handling
    if (initialValues.hasVpn) {
        if (requirements.deviceVpnCertificateType) {
            requiredCertificateTypes = requiredCertificateTypes.concat(requirements.deviceVpnCertificateType);
        }
    }

    const hasCertificateTypeFields: FieldsInterface = {
        hasCertificateType: (
            <Checkbox
                {...{
                    label: "hasCertificateType",
                }}
            />
        ),
    };
    const deviceTypeCertificateTypeFields: FieldsInterface = {
        certificateType: <Text {...{ hidden: true, label: "certificateType" }} />,
        hasCertificateType: <Checkbox {...{ hidden: true, label: "hasCertificateType" }} />, //field required for formik to handle initial values correctly
        certificateEncoding: (
            <RadioEnum
                {...{
                    enum: certificateEncoding,
                    label: "certificateEncoding",
                    help: "help.certificateEncoding",
                    ...collectionShowAndRequireOnTrue("hasCertificateType"),
                }}
            />
        ),
        enableCertificatesAutoRenew: (
            <Checkbox
                {...{
                    ...collectionShowOnTrue("hasCertificateType"),
                    label: "enableCertificatesAutoRenew",
                    help: "help.enableCertificatesAutoRenew",
                }}
            />
        ),
        certificatesAutoRenewDaysBefore: (
            <Text
                {...{
                    ...collectionShowAndRequireOnTrue("enableCertificatesAutoRenew"),
                    label: "certificatesAutoRenewDaysBefore",
                    help: "help.certificatesAutoRenewDaysBefore",
                }}
            />
        ),
        enableSubjectAltName: (
            <Checkbox
                {...{
                    ...collectionShowOnTrue("hasCertificateType"),
                    label: "enableSubjectAltName",
                }}
            />
        ),
        subjectAltNameType: (
            <RadioEnum
                {...{
                    enum: altNameType,
                    ...collectionShowAndRequireOnTrue("enableSubjectAltName"),
                    label: "subjectAltNameType",
                }}
            />
        ),
        subjectAltNameValue: (
            <Text
                {...{
                    ...collectionShowAndRequireOnTrue("enableSubjectAltName"),
                    label: "subjectAltNameValue",
                }}
            />
        ),
    };

    const fields = {
        name: <Text {...{ disabled: true, required: true, label: "deviceTypeName", help: "help.deviceTypeName" }} />,
        deviceName: (
            <Text
                {...{
                    disabled: true,
                    required: true,
                    label: "deviceTypeDeviceName",
                    help: "help.deviceTypeDeviceName",
                }}
            />
        ),
        certificateCommonNamePrefix: <Text {...{ disabled: true, required: true, help: true }} />,
        icon: (
            <SelectDeviceTypeIcon
                {...{
                    required: true,
                    help: true,
                }}
            />
        ),
        color: <ColorPicker {...{ required: true, help: true }} />,

        enableConnectionAggregation: <Checkbox />,
        connectionAggregationPeriod: (
            <Text {...{ help: true, ...showAndRequireOnTrue("enableConnectionAggregation") }} />
        ),

        authenticationMethod: (
            <RadioEnum
                {...{
                    enum: authenticationMethod,
                    required: true,
                    onChange(path, setFieldValue, event, value, onChange, values) {
                        onChange();
                        values.authenticationMethod = value;
                        if (isCredentialsSourceUsed(values)) {
                            if (!values.credentialsSource) {
                                setFieldValue("credentialsSource", "user");
                            }
                        }
                    },
                }}
            />
        ),
        credentialsSource: <RadioEnum {...{ enum: credentialsSource, ...getCredentialsSourceProps() }} />,
        deviceTypeSecretCredential: <SelectApi {...getDeviceTypeSecretCredentialProps(initialValues["id"])} />,
        deviceTypeCertificateTypeCredential: (
            <DeviceTypeCertificateTypeCredentialSelect
                {...{ certificateTypes: certificateTypes, ...getDeviceTypeCertificateTypeCredentialProps() }}
            />
        ),

        enableConfigMinRsrp: <Checkbox {...{ ...getEnableFieldProps(requirements, "hasConfig") }} />,
        configMinRsrp: <Text {...{ ...showAndRequireOnTrue("enableConfigMinRsrp") }} />,
        enableFirmwareMinRsrp: <Checkbox {...{ ...getEnableFieldProps(requirements, "hasFirmware") }} />,
        firmwareMinRsrp: <Text {...{ ...showAndRequireOnTrue("enableFirmwareMinRsrp") }} />,

        enableConfigLogs: <Checkbox />,

        hasCertificates: <Checkbox {...{ ...getFieldProps(requirements, "hasCertificates") }} disabled />,
        hasVpn: <Checkbox {...{ ...getFieldProps(requirements, "hasVpn") }} disabled />,
        hasEndpointDevices: (
            <Checkbox {...{ ...getFieldProps(requirements, "hasEndpointDevices", "hasVpn") }} disabled />
        ),
        virtualSubnetCidr: (
            <SelectEnum
                {...{
                    ...showAndRequireOnTrue("hasEndpointDevices"),
                    enum: cidr,
                    help: "help.deviceTypeVirtualSubnetCidr",
                }}
            />
        ),

        hasMasquerade: <Checkbox {...{ ...getFieldProps(requirements, "hasMasquerade", "hasVpn") }} />,
        masqueradeType: (
            <MasqueradeRadioEnum
                {...{ ...showAndRequireOnTrue("hasMasquerade"), help: "help.deviceTypeMasqueradeType" }}
            />
        ),

        hasDeviceCommands: <Checkbox {...{ ...getFieldProps(requirements, "hasDeviceCommands") }} disabled />,
        deviceCommandMaxRetries: <Text {...{ ...showAndRequireOnTrue("hasDeviceCommands") }} />,
        deviceCommandExpireDuration: <Text {...{ ...showAndRequireOnTrue("hasDeviceCommands") }} />,

        //This collection handles hasCertificateType field in same place as other hasX fields
        hasCertificateTypesCollection: (
            <CertificateTypeCollectionDeviceType
                {...{
                    disableAutoLabel: true,
                    path: "certificateTypes",
                    indent: false,
                    certificateTypes: certificateTypes,
                    fields: hasCertificateTypeFields,
                    requiredHasField: "hasCertificates",
                    requiredHasFieldsForCertificateTypes: [
                        {
                            fieldName: "hasCertificateType",
                            certificateTypes: requiredCertificateTypes,
                        },
                    ],
                    falsifyFieldsOnFalseForCertificateType: [
                        {
                            fieldName: "hasCertificateType",
                            certificateType: requirements.deviceVpnCertificateType,
                            fieldNamesToFalsify: [
                                "hasVpn",
                                "hasDeviceToNetworkConnection",
                                "hasMasquerade",
                                "hasEndpointDevices",
                            ],
                        },
                    ],
                    falsifyCollectionFieldsOnFalse: [
                        {
                            fieldName: "hasCertificateType",
                            collectionFieldNamesToFalsify: ["enableCertificatesAutoRenew", "enableSubjectAltName"],
                        },
                    ],
                }}
            />
        ),
        //This collection handles certificateTypes settings (like autorenew)
        //Both collections use same path and same values
        certificateTypes: (
            <CertificateTypeCollectionDeviceType
                {...{
                    showCollectionRowOnTrue: "hasCertificateType",
                    certificateTypeHeader: "label.certificateTypes",
                    indent: true,
                    certificateTypes: certificateTypes,
                    fields: deviceTypeCertificateTypeFields,
                }}
            />
        ),
    };

    return getFields(fields);
};

export default composeGetFields;
export { getEnableFieldProps, getIsEnableField };
