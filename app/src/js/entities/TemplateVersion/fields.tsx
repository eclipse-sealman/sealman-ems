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
    Textarea,
    MultiselectApi,
    FieldsInterface,
    SelectApi,
    Collection,
    SelectEnum,
} from "@arteneo/forge";
import MasqueradeRadioEnum from "~app/components/Form/fields/MasqueradeRadioEnum";
import { cidr } from "~app/enums/Cidr";
import VirtualIpHostPart from "~app/components/Form/fields/VirtualIpHostPart";
import { DeviceConfigurationTypeInterface } from "~app/entities/DeviceType/definitions";
import TemplateVersionReinstall from "~app/components/Form/fields/TemplateVersionReinstall";

const composeGetFields = (
    deviceType: DeviceConfigurationTypeInterface,
    limited: boolean,
    limitedVpn: boolean,
    templateId?: string | number
) => {
    const fields: FieldsInterface = {
        name: <Text {...{ required: true }} />,
        description: <Textarea />,
    };

    if (!limited && !limitedVpn) {
        fields.deviceDescription = <Textarea />;
    }

    fields.deviceLabels = <MultiselectApi {...{ endpoint: "/options/labels" }} />;
    fields.accessTags = <MultiselectApi {...{ required: limited, endpoint: "/options/access/tags" }} />;

    if (deviceType.hasConfig1) {
        fields.config1 = (
            <SelectApi
                {...{
                    label: "config",
                    labelVariables: { configName: deviceType.nameConfig1 },
                    endpoint: "/options/configs/1/" + deviceType.id,
                }}
            />
        );

        if (typeof templateId !== "undefined" && !deviceType?.hasAlwaysReinstallConfig1) {
            fields.reinstallConfig1 = (
                <TemplateVersionReinstall
                    {...{
                        configPath: "config1",
                        templateId,
                        label: "connectedDevicesReinstallName",
                        labelVariables: { name: deviceType.nameConfig1 },
                        required: true,
                    }}
                />
            );
        }
    }

    if (deviceType.hasConfig2) {
        fields.config2 = (
            <SelectApi
                {...{
                    label: "config",
                    labelVariables: { configName: deviceType.nameConfig2 },
                    endpoint: "/options/configs/2/" + deviceType.id,
                }}
            />
        );

        if (typeof templateId !== "undefined" && !deviceType?.hasAlwaysReinstallConfig2) {
            fields.reinstallConfig2 = (
                <TemplateVersionReinstall
                    {...{
                        configPath: "config2",
                        templateId,
                        label: "connectedDevicesReinstallName",
                        labelVariables: { name: deviceType.nameConfig2 },
                        required: true,
                    }}
                />
            );
        }
    }

    if (deviceType.hasConfig3) {
        fields.config3 = (
            <SelectApi
                {...{
                    label: "config",
                    labelVariables: { configName: deviceType.nameConfig3 },
                    endpoint: "/options/configs/3/" + deviceType.id,
                }}
            />
        );

        if (typeof templateId !== "undefined" && !deviceType?.hasAlwaysReinstallConfig3) {
            fields.reinstallConfig3 = (
                <TemplateVersionReinstall
                    {...{
                        configPath: "config3",
                        templateId,
                        label: "connectedDevicesReinstallName",
                        labelVariables: { name: deviceType.nameConfig3 },
                        required: true,
                    }}
                />
            );
        }
    }

    if (deviceType.hasFirmware1) {
        fields.firmware1 = (
            <SelectApi
                {...{
                    label: "firmware",
                    labelVariables: { firmwareName: deviceType.nameFirmware1 },
                    endpoint: "/options/firmwares/1/" + deviceType.id,
                }}
            />
        );

        if (typeof templateId !== "undefined") {
            fields.reinstallFirmware1 = (
                <TemplateVersionReinstall
                    {...{
                        configPath: "firmware1",
                        templateId,
                        label: "connectedDevicesReinstallName",
                        labelVariables: { name: deviceType.nameFirmware1 },
                        required: true,
                    }}
                />
            );
        }
    }

    if (deviceType.hasFirmware2) {
        fields.firmware2 = (
            <SelectApi
                {...{
                    label: "firmware",
                    labelVariables: { firmwareName: deviceType.nameFirmware2 },
                    endpoint: "/options/firmwares/2/" + deviceType.id,
                }}
            />
        );

        if (typeof templateId !== "undefined") {
            fields.reinstallFirmware2 = (
                <TemplateVersionReinstall
                    {...{
                        configPath: "firmware2",
                        templateId,
                        label: "connectedDevicesReinstallName",
                        labelVariables: { name: deviceType.nameFirmware2 },
                        required: true,
                    }}
                />
            );
        }
    }

    if (deviceType.hasFirmware3) {
        fields.firmware3 = (
            <SelectApi
                {...{
                    label: "firmware",
                    labelVariables: { firmwareName: deviceType.nameFirmware3 },
                    endpoint: "/options/firmwares/3/" + deviceType.id,
                }}
            />
        );

        if (typeof templateId !== "undefined") {
            fields.reinstallFirmware3 = (
                <TemplateVersionReinstall
                    {...{
                        configPath: "firmware3",
                        templateId,
                        label: "connectedDevicesReinstallName",
                        labelVariables: { name: deviceType.nameFirmware3 },
                        required: true,
                    }}
                />
            );
        }
    }

    if (!limited && !limitedVpn && deviceType.isMasqueradeAvailable) {
        fields.masqueradeType = <MasqueradeRadioEnum />;
        fields.masquerades = (
            <Collection
                {...{
                    hidden: (values) => values.masqueradeType !== "advanced",
                    fields: {
                        subnet: <Text {...{ required: true }} />,
                    },
                }}
            />
        );
    }

    if (!limited && !limitedVpn && deviceType.isEndpointDevicesAvailable) {
        fields.virtualSubnetCidr = <SelectEnum {...{ enum: cidr }} />;
    }

    if (!limited && !limitedVpn && deviceType.isEndpointDevicesAvailable) {
        fields.endpointDevices = (
            <Collection
                {...{
                    fields: {
                        name: <Text {...{ required: true }} />,
                        physicalIp: <Text {...{ required: true }} />,
                        virtualIpHostPart: <VirtualIpHostPart {...{ required: true }} />,
                        description: <Textarea {...{ fieldProps: { minRows: 1 } }} />,
                        accessTags: <MultiselectApi {...{ endpoint: "/options/access/tags" }} />,
                    },
                }}
            />
        );
    }

    if (deviceType.hasVariables) {
        fields.variables = (
            <Collection
                {...{
                    fields: {
                        name: <Text {...{ required: true }} />,
                        variableValue: <Textarea {...{ required: true, fieldProps: { minRows: 1 } }} />,
                    },
                }}
            />
        );
    }

    return getFields(fields);
};

export default composeGetFields;
