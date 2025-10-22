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
    ButtonDialogFormAlertFieldset,
    Checkbox,
    FieldsInterface,
    SelectApi,
    SelectApiProps,
    useSnackbar,
} from "@arteneo/forge";
import { Box } from "@mui/material";
import { EditOutlined } from "@mui/icons-material";
import { FormikValues, useFormikContext } from "formik";
import { DeviceConfigurationTypeInterface } from "~app/entities/DeviceType/definitions";
import { useUser } from "~app/contexts/User";
import { TemplateInterface } from "~app/entities/Template/definitions";
import { TemplateVersionInterface } from "~app/entities/TemplateVersion/definitions";
import SelectApiGroupedByDeviceType from "~app/components/Form/fields/SelectApiGroupedByDeviceType";

interface TemplateSelectApiProps extends SelectApiProps {
    deviceType: DeviceConfigurationTypeInterface;
}

const TemplateSelectApi = ({ deviceType, ...selectApiProps }: TemplateSelectApiProps) => {
    const { isAccessGranted } = useUser();
    const { values, setFieldValue } = useFormikContext<FormikValues>();
    const { showWarning } = useSnackbar();

    const fields: FieldsInterface = {
        template: (
            <SelectApiGroupedByDeviceType
                {...{
                    endpoint: "/options/templates/" + deviceType.id,
                    onChange: (path, setFieldValue, value, defaultOnChange) => {
                        defaultOnChange();

                        if (isAccessGranted({ admin: true }) && !deviceType?.isEndpointDevicesAvailable) {
                            setFieldValue("applyEndpointDevices", false);
                        }

                        if (!deviceType?.hasVariables) {
                            setFieldValue("applyVariables", false);
                        }

                        if (isAccessGranted({ admin: true }) && !deviceType?.isMasqueradeAvailable) {
                            setFieldValue("applyMasquerade", false);
                        }

                        if (isAccessGranted({ admin: true }) && !deviceType?.isVpnAvailable) {
                            setFieldValue("applyDeviceDescription", false);
                        }

                        if (!deviceType) {
                            setFieldValue("applyLabels", false);
                            setFieldValue("applyAccessTags", false);
                        }
                    },
                }}
            />
        ),
        applyDeviceDescription: (
            <Checkbox {...{ hidden: (values) => !values?.template || !deviceType?.isVpnAvailable }} />
        ),
        applyEndpointDevices: (
            <Checkbox {...{ hidden: (values) => !values?.template || !deviceType?.isEndpointDevicesAvailable }} />
        ),
        applyVariables: <Checkbox {...{ hidden: (values) => !values?.template || !deviceType?.hasVariables }} />,
        applyMasquerade: (
            <Checkbox {...{ hidden: (values) => !values?.template || !deviceType?.isMasqueradeAvailable }} />
        ),
        applyAccessTags: <Checkbox {...{ hidden: (values) => !values?.template || !deviceType }} />,
        applyLabels: <Checkbox {...{ hidden: (values) => !values?.template || !deviceType }} />,
        reinstallConfig1: (
            <Checkbox
                {...{
                    label: "reinstallConfig",
                    labelVariables: { config: deviceType?.nameConfig1 },
                    hidden: (values) =>
                        !values?.template || !deviceType?.hasConfig1 || deviceType?.hasAlwaysReinstallConfig1,
                }}
            />
        ),
        reinstallConfig2: (
            <Checkbox
                {...{
                    label: "reinstallConfig",
                    labelVariables: { config: deviceType?.nameConfig2 },
                    hidden: (values) =>
                        !values?.template || !deviceType?.hasConfig2 || deviceType?.hasAlwaysReinstallConfig2,
                }}
            />
        ),
        reinstallConfig3: (
            <Checkbox
                {...{
                    label: "reinstallConfig",
                    labelVariables: { config: deviceType?.nameConfig3 },
                    hidden: (values) =>
                        !values?.template || !deviceType?.hasConfig3 || deviceType?.hasAlwaysReinstallConfig3,
                }}
            />
        ),
        reinstallFirmware1: (
            <Checkbox
                {...{
                    label: "reinstallFirmware",
                    labelVariables: { firmware: deviceType?.nameFirmware1 },
                    hidden: (values) => !values?.template || !deviceType?.hasFirmware1,
                }}
            />
        ),
        reinstallFirmware2: (
            <Checkbox
                {...{
                    label: "reinstallFirmware",
                    labelVariables: { firmware: deviceType?.nameFirmware2 },
                    hidden: (values) => !values?.template || !deviceType?.hasFirmware2,
                }}
            />
        ),
        reinstallFirmware3: (
            <Checkbox
                {...{
                    label: "reinstallFirmware",
                    labelVariables: { firmware: deviceType?.nameFirmware3 },
                    hidden: (values) => !values?.template || !deviceType?.hasFirmware3,
                }}
            />
        ),
    };

    if (!isAccessGranted({ admin: true })) {
        delete fields.applyDeviceDescription;
        delete fields.applyEndpointDevices;
        delete fields.applyMasquerade;
    }

    const isStaging = values?.staging ? true : false;

    const applyTemplateVersion = (values: FormikValues, templateVersion: TemplateVersionInterface) => {
        if (values.applyVariables) {
            setFieldValue(
                "variables",
                templateVersion.variables?.map((variable) => ({
                    name: variable.name,
                    variableValue: variable.variableValue,
                })) ?? []
            );
        }

        if (values.applyAccessTags) {
            setFieldValue("accessTags", templateVersion.accessTags?.map((accessTag) => accessTag.id) ?? []);
        }

        if (values.applyLabels) {
            setFieldValue("labels", templateVersion.deviceLabels?.map((label) => label.id) ?? []);
        }

        if (values.applyMasquerade) {
            setFieldValue("masqueradeType", templateVersion.masqueradeType);
            setFieldValue(
                "masquerades",
                templateVersion.masquerades?.map((masquerade) => ({
                    subnet: masquerade.subnet,
                })) ?? []
            );
        }

        if (values.applyDeviceDescription) {
            setFieldValue("description", templateVersion.deviceDescription);
        }

        if (values.applyEndpointDevices) {
            setFieldValue("virtualSubnetCidr", templateVersion.virtualSubnetCidr);
            setFieldValue(
                "endpointDevices",
                templateVersion.endpointDevices?.map((endpointDevice) => ({
                    name: endpointDevice.name,
                    physicalIp: endpointDevice.physicalIp,
                    virtualIpHostPart: endpointDevice.virtualIpHostPart,
                    description: endpointDevice.description,
                    accessTags: endpointDevice.accessTags?.map((accessTag) => accessTag.id) ?? [],
                })) ?? []
            );
        }

        const reinstallFlags: string[] = [
            "reinstallConfig1",
            "reinstallConfig2",
            "reinstallConfig3",
            "reinstallFirmware1",
            "reinstallFirmware2",
            "reinstallFirmware3",
        ];
        reinstallFlags.forEach((reinstallFlag) => {
            if (values[reinstallFlag]) {
                setFieldValue(reinstallFlag, true);
            }
        });
    };

    return (
        <Box {...{ sx: { display: "flex", gap: 1 } }}>
            <Box {...{ sx: { display: "flex", flexDirection: "column", flexGrow: 1 } }}>
                <SelectApi {...selectApiProps} />
            </Box>
            <ButtonDialogFormAlertFieldset
                {...{
                    label: "templateSelectApi.action",
                    variant: "contained",
                    color: "info",
                    startIcon: <EditOutlined />,
                    dialogProps: {
                        title: "templateSelectApi.title",
                        label: "templateSelectApi.label." + (isStaging ? "staging" : "production"),
                        formProps: {
                            fields,
                            snackbarLabel: "templateSelectApi.snackbar.success",
                            endpoint: (values) => {
                                if (!values?.template) {
                                    // This is a special case when we do not have a template selected. We want to keep ButtonDialogFormAlertFieldset flow, so we send any valid request to the backend.
                                    return {
                                        method: "get",
                                        url: "/options/templates/" + deviceType.id,
                                    };
                                }

                                return {
                                    method: "get",
                                    url: "/template/" + values.template,
                                };
                            },
                            onSubmitSuccess: (defaultOnSubmitSuccess, values, helpers, response, onClose) => {
                                setFieldValue("template", values.template);

                                if (values.template) {
                                    const template: TemplateInterface = response.data;
                                    const templateVersion = (
                                        isStaging ? template.stagingTemplate : template.productionTemplate
                                    ) as TemplateVersionInterface;

                                    if (!templateVersion) {
                                        showWarning(
                                            "templateSelectApi.snackbar.warning." +
                                                (isStaging
                                                    ? "missingStagingTemplateVersion"
                                                    : "missingProductionTemplateVersion")
                                        );
                                        onClose();
                                        return;
                                    }

                                    applyTemplateVersion(values, templateVersion);
                                }

                                defaultOnSubmitSuccess();
                                onClose();
                            },
                        },
                    },
                }}
            />
        </Box>
    );
};

TemplateSelectApi.defaultProps = {
    // eslint-disable-next-line
    transformInitialValue: (value: any) => {
        // Backend API is serializing it as object
        if (typeof value?.id !== "undefined") {
            return value.id;
        }

        return value;
    },
};

export default TemplateSelectApi;
export { SelectApiProps as TemplateSelectApiProps };
