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
    authenticationMethod,
    credentialsSource,
    certificateEncoding,
    communicationProcedure,
    formatConfig,
} from "~app/entities/DeviceType/enums";
import {
    collectionShowAndRequireOnTrue,
    collectionShowOnTrue,
    showAndRequireOnTrue,
    showOnTrue,
} from "~app/utilities/fields";
import { CommunicationProcedureRequirements } from "~app/entities/DeviceType/definitions";
import SelectDeviceTypeIcon from "~app/components/Form/fields/SelectDeviceTypeIcon";
import { FormikValues, getIn } from "formik";
import MasqueradeRadioEnum from "~app/components/Form/fields/MasqueradeRadioEnum";
import { cidr } from "~app/enums/Cidr";
import { fieldRequirement } from "~app/enums/FieldRequirement";
import { Translation } from "react-i18next";
import CertificateTypeCollectionDeviceType from "~app/components/Form/fields/CertificateTypeCollectionDeviceType";
import {
    isCertificateTypeCredentialUsed,
    isCredentialsSourceUsed,
    isDeviceSecretCredentialUsed,
} from "~app/entities/DeviceType/utilities";
import DeviceTypeCertificateTypeCredentialSelect from "~app/entities/DeviceType/fields/DeviceTypeCertificateTypeCredentialSelect";

const falsifyFieldsValueOnChange = (
    // eslint-disable-next-line
    setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void,
    checked: boolean,
    onChange: () => void,
    values: FormikValues,
    names: string[] = []
) => {
    if (!checked) {
        names.forEach((name: string) => {
            if (values[name]) {
                setFieldValue(name, false);
            }
        });
    }

    onChange();
};

const falsifyCertificateTypesFieldsValueOnChange = (
    // eslint-disable-next-line
    setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void,
    checked: boolean,
    values: FormikValues
) => {
    if (!checked) {
        if (Array.isArray(values?.certificateTypes)) {
            values.certificateTypes.forEach((deviceTypeCertificateType: FormikValues, index: number) => {
                const deviceTypeCertificateTypePath = "certificateTypes." + index + ".";
                if (getIn(values, deviceTypeCertificateTypePath + "hasCertificateType", false)) {
                    setFieldValue(deviceTypeCertificateTypePath + "hasCertificateType", false);
                }
                if (getIn(values, deviceTypeCertificateTypePath + "enableCertificatesAutoRenew", false)) {
                    setFieldValue(deviceTypeCertificateTypePath + "enableCertificatesAutoRenew", false);
                }
                if (getIn(values, deviceTypeCertificateTypePath + "enableSubjectAltName", false)) {
                    setFieldValue(deviceTypeCertificateTypePath + "enableSubjectAltName", false);
                }
            });
        }
    }
};

const getFieldWithRequiredProps = (
    requirements: CommunicationProcedureRequirements,
    requiredField?: string,
    help?: string,
    requireDeviceVpnCertificate = false
): FieldInterface => {
    if (!requiredField && !requireDeviceVpnCertificate) {
        return {
            disabled: false,
        };
    }

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

    const resolvedHelp = (values: FormikValues) => {
        const helpText = help !== undefined ? <Translation>{(t) => t(help)}</Translation> : undefined;

        if (!isRequirementMet(values)) {
            return (
                <>
                    <Translation>
                        {(t) =>
                            t(
                                requireDeviceVpnCertificate
                                    ? "label.enableDeviceVpnCertificate"
                                    : "label." + requiredField ?? "unknown"
                            ) +
                            " " +
                            t("help.requiredFieldEnableToUse") +
                            (helpText ? ". " : "")
                        }
                    </Translation>
                    {helpText}
                </>
            );
        } else {
            return helpText;
        }
    };

    return {
        disabled: (values: FormikValues) => !isRequirementMet(values),
        help: resolvedHelp,
        disableTranslateHelp: true,
    };
};

const getFieldProps = (
    requirements: CommunicationProcedureRequirements,
    name: string,
    requiredField?: string,
    help?: string,
    requireDeviceVpnCertificate = false
): FieldInterface => {
    if (requirements.communicationProcedureRequirementsRequired.includes(name)) {
        return {
            required: true,
            disabled: true,
            help: <Translation>{(t) => t("help.fieldRequiredByCommunicationProcedure")}</Translation>,
            disableTranslateHelp: true,
        };
    }
    if (requirements.communicationProcedureRequirementsOptional.includes(name)) {
        return Object.assign(
            {
                required: false,
            },
            getFieldWithRequiredProps(requirements, requiredField, help, requireDeviceVpnCertificate)
        );
    }
    return {
        required: false,
        disabled: true,
        hidden: true,
    };
};

const getAlwaysReinstallConfigFieldProps = (
    requirements: CommunicationProcedureRequirements,
    name: string,
    requiredField: string
): FieldInterface => {
    const help = "help." + name;

    if (requirements.communicationProcedureRequirementsRequired.includes(name)) {
        return {
            required: true,
            disabled: true,
            help: <Translation>{(t) => t("help.fieldRequiredByCommunicationProcedure")}</Translation>,
            disableTranslateHelp: true,
        };
    }
    if (requirements.communicationProcedureRequirementsOptional.includes(name)) {
        return Object.assign(
            {
                required: false,
                hidden: (values: FormikValues) => values[requiredField] !== true,
            },
            getFieldWithRequiredProps(requirements, requiredField, help)
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

const getCredentialsSourceProps = (createForm: boolean): FieldInterface => {
    if (createForm) {
        return {
            hidden: (values: FormikValues) => (isCredentialsSourceUsed(values) ? false : true),
            required: false,
            disabled: true,
            help: "help.createCredentialsSource",
        };
    }
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
    deviceTypeId: undefined | string | number = undefined
) => {
    const certificateTypes = requirements.communicationProcedureCertificateCategoryRequired.concat(
        requirements.communicationProcedureCertificateCategoryOptional
    );

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
        name: <Text {...{ required: true, label: "deviceTypeName", help: "help.deviceTypeName" }} />,
        deviceName: <Text {...{ required: true, label: "deviceTypeDeviceName", help: "help.deviceTypeDeviceName" }} />,
        certificateCommonNamePrefix: <Text {...{ required: true, help: true }} />,
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

        routePrefix: <Text {...{ help: true, required: true }} />,
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
        credentialsSource: (
            <RadioEnum {...{ enum: credentialsSource, ...getCredentialsSourceProps(deviceTypeId == undefined) }} />
        ),
        deviceTypeSecretCredential:
            deviceTypeId == undefined ? (
                <Text {...{ hidden: true }} /> //Hidden field - added for cleaner code in fieldset
            ) : (
                <SelectApi {...getDeviceTypeSecretCredentialProps(deviceTypeId)} />
            ),
        deviceTypeCertificateTypeCredential: (
            <DeviceTypeCertificateTypeCredentialSelect
                {...{ certificateTypes: certificateTypes, ...getDeviceTypeCertificateTypeCredentialProps() }}
            />
        ),
        communicationProcedure: <RadioEnum {...{ enum: communicationProcedure, help: true, required: true }} />,

        hasFirmware1: <Checkbox {...{ ...getFieldProps(requirements, "hasFirmware1") }} />,
        nameFirmware1: <Text {...{ ...showAndRequireOnTrue("hasFirmware1") }} />,
        customUrlFirmware1: <Text {...{ help: "help.customUrlFirmware", ...showOnTrue("hasFirmware1") }} />,
        hasFirmware2: <Checkbox {...{ ...getFieldProps(requirements, "hasFirmware2") }} />,
        nameFirmware2: <Text {...{ ...showAndRequireOnTrue("hasFirmware2") }} />,
        customUrlFirmware2: <Text {...{ help: "help.customUrlFirmware", ...showOnTrue("hasFirmware2") }} />,
        hasFirmware3: <Checkbox {...{ ...getFieldProps(requirements, "hasFirmware3") }} />,
        nameFirmware3: <Text {...{ ...showAndRequireOnTrue("hasFirmware3") }} />,
        customUrlFirmware3: <Text {...{ help: "help.customUrlFirmware", ...showOnTrue("hasFirmware3") }} />,
        hasConfig1: <Checkbox {...{ ...getFieldProps(requirements, "hasConfig1") }} />,
        hasAlwaysReinstallConfig1: (
            <Checkbox
                {...{
                    ...getAlwaysReinstallConfigFieldProps(requirements, "hasAlwaysReinstallConfig1", "hasConfig1"),
                }}
            />
        ),
        nameConfig1: <Text {...{ ...showAndRequireOnTrue("hasConfig1") }} />,
        formatConfig1: <RadioEnum {...{ enum: formatConfig, ...showAndRequireOnTrue("hasConfig1") }} />,
        hasConfig2: <Checkbox {...{ ...getFieldProps(requirements, "hasConfig2") }} />,
        hasAlwaysReinstallConfig2: (
            <Checkbox
                {...{
                    ...getAlwaysReinstallConfigFieldProps(requirements, "hasAlwaysReinstallConfig2", "hasConfig2"),
                }}
            />
        ),
        nameConfig2: <Text {...{ ...showAndRequireOnTrue("hasConfig2") }} />,
        formatConfig2: <RadioEnum {...{ enum: formatConfig, ...showAndRequireOnTrue("hasConfig2") }} />,
        hasConfig3: <Checkbox {...{ ...getFieldProps(requirements, "hasConfig3") }} />,
        hasAlwaysReinstallConfig3: (
            <Checkbox
                {...{
                    ...getAlwaysReinstallConfigFieldProps(requirements, "hasAlwaysReinstallConfig3", "hasConfig3"),
                }}
            />
        ),
        nameConfig3: <Text {...{ ...showAndRequireOnTrue("hasConfig3") }} />,
        formatConfig3: <RadioEnum {...{ enum: formatConfig, ...showAndRequireOnTrue("hasConfig3") }} />,

        enableConfigMinRsrp: <Checkbox {...{ ...getEnableFieldProps(requirements, "hasConfig") }} />,
        configMinRsrp: <Text {...{ ...showAndRequireOnTrue("enableConfigMinRsrp") }} />,
        enableFirmwareMinRsrp: <Checkbox {...{ ...getEnableFieldProps(requirements, "hasFirmware") }} />,
        firmwareMinRsrp: <Text {...{ ...showAndRequireOnTrue("enableFirmwareMinRsrp") }} />,

        enableConfigLogs: <Checkbox />,

        hasTemplates: <Checkbox {...{ ...getFieldProps(requirements, "hasTemplates") }} />,
        hasGsm: <Checkbox {...{ ...getFieldProps(requirements, "hasGsm") }} />,
        hasRequestConfig: <Checkbox {...{ ...getFieldProps(requirements, "hasRequestConfig") }} />,
        hasRequestDiagnose: <Checkbox {...{ ...getFieldProps(requirements, "hasRequestDiagnose") }} />,

        fieldSerialNumber: <RadioEnum {...{ enum: fieldRequirement }} />,
        fieldImsi: <RadioEnum {...{ enum: fieldRequirement }} />,
        fieldModel: <RadioEnum {...{ enum: fieldRequirement }} />,
        fieldRegistrationId: <RadioEnum {...{ enum: fieldRequirement }} />,
        fieldEndorsementKey: <RadioEnum {...{ enum: fieldRequirement }} />,
        fieldHardwareVersion: <RadioEnum {...{ enum: fieldRequirement }} />,

        hasVariables: (
            <Checkbox
                {...{
                    onChange: (
                        path: string,
                        // eslint-disable-next-line
                        setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void,
                        event: React.SyntheticEvent,
                        checked: boolean,
                        onChange: () => void,
                        values: FormikValues
                    ) => {
                        falsifyCertificateTypesFieldsValueOnChange(setFieldValue, checked, values);
                        falsifyFieldsValueOnChange(setFieldValue, checked, onChange, values, [
                            "hasCertificates",
                            "hasVpn",
                            "hasDeviceToNetworkConnection",
                            "hasEndpointDevices",
                            "hasMasquerade",
                        ]);
                    },
                    ...getFieldProps(requirements, "hasVariables"),
                }}
            />
        ),
        hasCertificates: (
            <Checkbox
                {...{
                    onChange: (
                        path: string,
                        // eslint-disable-next-line
                        setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void,
                        event: React.SyntheticEvent,
                        checked: boolean,
                        onChange: () => void,
                        values: FormikValues
                    ) => {
                        falsifyCertificateTypesFieldsValueOnChange(setFieldValue, checked, values);
                        falsifyFieldsValueOnChange(setFieldValue, checked, onChange, values, [
                            "hasVpn",
                            "hasDeviceToNetworkConnection",
                            "hasMasquerade",
                            "hasEndpointDevices",
                        ]);
                    },
                    ...getFieldProps(requirements, "hasCertificates", "hasVariables"),
                }}
            />
        ),
        hasVpn: (
            <Checkbox
                {...{
                    onChange: (
                        path: string,
                        // eslint-disable-next-line
                        setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void,
                        event: React.SyntheticEvent,
                        checked: boolean,
                        onChange: () => void,
                        values: FormikValues
                    ) =>
                        falsifyFieldsValueOnChange(setFieldValue, checked, onChange, values, [
                            "hasMasquerade",
                            "hasEndpointDevices",
                        ]),
                    ...getFieldProps(requirements, "hasVpn", undefined, undefined, true),
                }}
            />
        ),
        virtualSubnetCidr: (
            <SelectEnum {...{ ...showAndRequireOnTrue("hasEndpointDevices"), enum: cidr, help: true }} />
        ),

        hasEndpointDevices: <Checkbox {...{ ...getFieldProps(requirements, "hasEndpointDevices", "hasVpn") }} />,

        hasMasquerade: <Checkbox {...{ ...getFieldProps(requirements, "hasMasquerade", "hasVpn") }} />,
        masqueradeType: (
            <MasqueradeRadioEnum
                {...{ ...showAndRequireOnTrue("hasMasquerade"), help: "help.deviceTypeMasqueradeType" }}
            />
        ),

        hasDeviceCommands: <Checkbox {...{ ...getFieldProps(requirements, "hasDeviceCommands") }} />,
        deviceCommandMaxRetries: <Text {...{ ...showAndRequireOnTrue("hasDeviceCommands") }} />,
        deviceCommandExpireDuration: <Text {...{ ...showAndRequireOnTrue("hasDeviceCommands") }} />,

        hasDeviceToNetworkConnection: (
            <Checkbox
                {...{ ...getFieldProps(requirements, "hasDeviceToNetworkConnection", undefined, undefined, true) }}
            />
        ),
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
                            certificateTypes: requirements.communicationProcedureCertificateCategoryRequired,
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
