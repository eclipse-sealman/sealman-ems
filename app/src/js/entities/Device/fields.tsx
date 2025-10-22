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
    MultiselectApi,
    FieldsInterface,
    Collection,
    IndexedCollection,
    SelectEnum,
    Textarea,
    Checkbox,
} from "@arteneo/forge";
import MasqueradeRadioEnum from "~app/components/Form/fields/MasqueradeRadioEnum";
import { cidr } from "~app/enums/Cidr";
import VirtualIpHostPart, { VirtualIpHostPartProps } from "~app/components/Form/fields/VirtualIpHostPart";
import { DeviceConfigurationTypeInterface } from "~app/entities/DeviceType/definitions";
import { isFieldHidden, isFieldRequired } from "~app/enums/FieldRequirement";
import TemplateSelectApi from "~app/components/Form/fields/TemplateSelectApi";
import DeviceCertificateAutomaticBehaviorCollection from "~app/components/Form/fields/DeviceCertificateAutomaticBehaviorCollection";

const composeGetFields = (
    deviceType: DeviceConfigurationTypeInterface,
    accessTagsRequired = false,
    virtualSubnetIpSortable?: number,
    getVirtualSubnetCidr?: VirtualIpHostPartProps["getVirtualSubnetCidr"],
    roleVpnEndpointDevices = false
) => {
    const fields: FieldsInterface = {
        name: <Text {...{ required: true }} />,
    };

    if (!isFieldHidden(deviceType.fieldSerialNumber)) {
        fields.serialNumber = (
            <Text
                {...{
                    required: isFieldRequired(deviceType.fieldSerialNumber),
                }}
            />
        );
    }

    if (deviceType.hasTemplates) {
        fields.template = <TemplateSelectApi {...{ endpoint: "/options/templates", disabled: true, deviceType }} />;
    }

    if (deviceType.hasGsm) {
        if (!isFieldHidden(deviceType.fieldImsi)) {
            fields.imsi = <Text {...{ required: isFieldRequired(deviceType.fieldImsi) }} />;
        }
        fields.imei = <Text />;
    }

    if (!isFieldHidden(deviceType.fieldModel)) {
        fields.model = <Text {...{ required: isFieldRequired(deviceType.fieldModel) }} />;
    }

    if (!isFieldHidden(deviceType.fieldRegistrationId)) {
        fields.registrationId = <Text {...{ required: isFieldRequired(deviceType.fieldRegistrationId) }} />;
    }

    if (!isFieldHidden(deviceType.fieldEndorsementKey)) {
        fields.endorsementKey = <Text {...{ required: isFieldRequired(deviceType.fieldEndorsementKey) }} />;
    }

    if (!isFieldHidden(deviceType.fieldHardwareVersion)) {
        fields.hardwareVersion = <Text {...{ required: isFieldRequired(deviceType.fieldHardwareVersion) }} />;
    }

    fields.description = <Textarea />;
    fields.labels = <MultiselectApi {...{ endpoint: "/options/labels" }} />;
    fields.accessTags = <MultiselectApi {...{ required: accessTagsRequired, endpoint: "/options/access/tags" }} />;

    if (deviceType.hasFirmware1) {
        fields.reinstallFirmware1 = (
            <Checkbox
                {...{
                    label: "reinstallFirmware",
                    labelVariables: { firmware: deviceType.nameFirmware1 },
                }}
            />
        );
    }

    if (deviceType.hasFirmware2) {
        fields.reinstallFirmware2 = (
            <Checkbox
                {...{
                    label: "reinstallFirmware",
                    labelVariables: { firmware: deviceType.nameFirmware2 },
                }}
            />
        );
    }

    if (deviceType.hasFirmware3) {
        fields.reinstallFirmware3 = (
            <Checkbox
                {...{
                    label: "reinstallFirmware",
                    labelVariables: { firmware: deviceType.nameFirmware3 },
                }}
            />
        );
    }

    if (deviceType.hasConfig1 && !deviceType.hasAlwaysReinstallConfig1) {
        fields.reinstallConfig1 = (
            <Checkbox
                {...{
                    label: "reinstallConfig",
                    labelVariables: { config: deviceType.nameConfig1 },
                }}
            />
        );
    }

    if (deviceType.hasConfig2 && !deviceType.hasAlwaysReinstallConfig2) {
        fields.reinstallConfig2 = (
            <Checkbox
                {...{
                    label: "reinstallConfig",
                    labelVariables: { config: deviceType.nameConfig2 },
                }}
            />
        );
    }

    if (deviceType.hasConfig3 && !deviceType.hasAlwaysReinstallConfig3) {
        fields.reinstallConfig3 = (
            <Checkbox
                {...{
                    label: "reinstallConfig",
                    labelVariables: { config: deviceType.nameConfig3 },
                }}
            />
        );
    }

    if (deviceType.hasRequestConfig) {
        fields.requestConfigData = <Checkbox />;
    }

    if (deviceType.hasRequestDiagnose) {
        fields.requestDiagnoseData = <Checkbox />;
    }

    if (deviceType.hasTemplates) {
        fields.staging = <Checkbox />;
    }

    fields.enabled = <Checkbox />;
    fields.certificateBehaviours = <DeviceCertificateAutomaticBehaviorCollection />;

    if (deviceType.isEndpointDevicesAvailable) {
        fields.virtualSubnetCidr = <SelectEnum {...{ required: true, enum: cidr }} />;
    }

    if (deviceType.isMasqueradeAvailable) {
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

    if (deviceType.isEndpointDevicesAvailable) {
        // Note! Endpoint devices collection should be last to match ugly CSS selector in app/src/js/routes/DeviceEdit.tsx
        // Note! Endpoint devices fields should be ordered in a way they match ugly CSS selector in app/src/js/routes/DeviceEdit.tsx

        // In case of roleVpnEndpointDevices, it should be IndexedCollection instead of standard Collection
        if (roleVpnEndpointDevices) {
            fields.endpointDevices = (
                <IndexedCollection
                    {...{
                        fields: {
                            name: <Text {...{ required: true }} />,
                            physicalIp: <Text {...{ required: true }} />,
                            virtualIpHostPart: (
                                <VirtualIpHostPart
                                    {...{ required: true, virtualSubnetIpSortable, getVirtualSubnetCidr }}
                                />
                            ),
                            description: <Textarea {...{ fieldProps: { minRows: 1 } }} />,
                            accessTags: <MultiselectApi {...{ endpoint: "/options/access/tags" }} />,
                        },
                    }}
                />
            );
        } else {
            fields.endpointDevices = (
                <Collection
                    {...{
                        fields: {
                            name: <Text {...{ required: true }} />,
                            physicalIp: <Text {...{ required: true }} />,
                            virtualIpHostPart: (
                                <VirtualIpHostPart
                                    {...{ required: true, virtualSubnetIpSortable, getVirtualSubnetCidr }}
                                />
                            ),
                            description: <Textarea {...{ fieldProps: { minRows: 1 } }} />,
                            accessTags: <MultiselectApi {...{ endpoint: "/options/access/tags" }} />,
                        },
                    }}
                />
            );
        }
    }

    return getFields(fields);
};

export default composeGetFields;
