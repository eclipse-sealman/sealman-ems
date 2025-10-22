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
import { Optional, BatchFormMultiAlert, BatchFormMultiAlertProps, Checkbox, FieldsInterface } from "@arteneo/forge";
import { DeviceConfigurationTypeInterface } from "~app/entities/DeviceType/definitions";
import { useUser } from "~app/contexts/User";
import SelectApiGroupedByDeviceType from "~app/components/Form/fields/SelectApiGroupedByDeviceType";

type BatchTemplateApplyProps = Optional<BatchFormMultiAlertProps, "dialogProps">;

type DeviceTypeType = Pick<
    DeviceConfigurationTypeInterface,
    | "id"
    | "representation"
    | "isEndpointDevicesAvailable"
    | "isMasqueradeAvailable"
    | "isVpnAvailable"
    | "hasVariables"
    | "hasConfig1"
    | "hasAlwaysReinstallConfig1"
    | "nameConfig1"
    | "hasConfig2"
    | "hasAlwaysReinstallConfig2"
    | "nameConfig2"
    | "hasConfig3"
    | "hasAlwaysReinstallConfig3"
    | "nameConfig3"
    | "hasFirmware1"
    | "nameFirmware1"
    | "hasFirmware2"
    | "nameFirmware2"
    | "hasFirmware3"
    | "nameFirmware3"
>;

const BatchTemplateApply = (props: BatchTemplateApplyProps) => {
    const { isAccessGranted } = useUser();
    const [deviceType, setDeviceType] = React.useState<undefined | DeviceTypeType>(undefined);

    const fields: FieldsInterface = {
        template: (
            <SelectApiGroupedByDeviceType
                {...{
                    endpoint: "/options/templates",
                    onChange: (path, setFieldValue, value, defaultOnChange) => {
                        defaultOnChange();

                        // eslint-disable-next-line
                        const deviceType: DeviceTypeType = (value as any)?.deviceType;
                        setDeviceType(deviceType);

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
        applyDeviceDescription: <Checkbox {...{ hidden: !deviceType?.isVpnAvailable }} />,
        applyEndpointDevices: <Checkbox {...{ hidden: !deviceType?.isEndpointDevicesAvailable }} />,
        applyVariables: <Checkbox {...{ hidden: !deviceType?.hasVariables }} />,
        applyMasquerade: <Checkbox {...{ hidden: !deviceType?.isMasqueradeAvailable }} />,
        applyAccessTags: <Checkbox {...{ hidden: !deviceType }} />,
        applyLabels: <Checkbox {...{ hidden: !deviceType }} />,
        reinstallConfig1: (
            <Checkbox
                {...{
                    label: "reinstallConfig",
                    labelVariables: { config: deviceType?.nameConfig1 },
                    hidden: !deviceType?.hasConfig1 || deviceType?.hasAlwaysReinstallConfig1,
                }}
            />
        ),
        reinstallConfig2: (
            <Checkbox
                {...{
                    label: "reinstallConfig",
                    labelVariables: { config: deviceType?.nameConfig2 },
                    hidden: !deviceType?.hasConfig2 || deviceType?.hasAlwaysReinstallConfig2,
                }}
            />
        ),
        reinstallConfig3: (
            <Checkbox
                {...{
                    label: "reinstallConfig",
                    labelVariables: { config: deviceType?.nameConfig3 },
                    hidden: !deviceType?.hasConfig3 || deviceType?.hasAlwaysReinstallConfig3,
                }}
            />
        ),
        reinstallFirmware1: (
            <Checkbox
                {...{
                    label: "reinstallFirmware",
                    labelVariables: { firmware: deviceType?.nameFirmware1 },
                    hidden: !deviceType?.hasFirmware1,
                }}
            />
        ),
        reinstallFirmware2: (
            <Checkbox
                {...{
                    label: "reinstallFirmware",
                    labelVariables: { firmware: deviceType?.nameFirmware2 },
                    hidden: !deviceType?.hasFirmware2,
                }}
            />
        ),
        reinstallFirmware3: (
            <Checkbox
                {...{
                    label: "reinstallFirmware",
                    labelVariables: { firmware: deviceType?.nameFirmware3 },
                    hidden: !deviceType?.hasFirmware3,
                }}
            />
        ),
    };

    if (!isAccessGranted({ admin: true })) {
        delete fields.applyDeviceDescription;
        delete fields.applyEndpointDevices;
        delete fields.applyMasquerade;
    }

    return (
        <BatchFormMultiAlert
            {...{
                label: "batch.device.templateApply.action",
                ...props,
                dialogProps: {
                    title: "batch.device.templateApply.title",
                    label: "batch.device.templateApply.label",
                    onClose: (defaultOnClose) => {
                        defaultOnClose();
                        setDeviceType(undefined);
                    },
                    formProps: {
                        fields,
                        resultDenyKey: "templateApply",
                        endpoint: (result) => "/device/" + result.id + "/template/apply",
                    },
                    ...props.dialogProps,
                },
            }}
        />
    );
};

export default BatchTemplateApply;
export { BatchTemplateApplyProps };
