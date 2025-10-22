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
    Checkbox,
    CheckboxProps,
    Collection,
    getFields,
    RadioEnum,
    RadioFalseTrue,
    SelectEnum,
    Text,
} from "@arteneo/forge";
import { radiusAuthenticationProtocol, radiusUserRole, RadiusUserRoleType } from "~app/entities/Configuration/enums";
import { showAndRequireOnTrue, showOnTrue } from "~app/utilities/fields";
import { FormikValues, getIn } from "formik";
import TextWithObfuscatedValue from "~app/components/Form/fields/TextWithObfuscatedValue";

const roleVpnEndpointDevicesSupported = (radiusUserRole: RadiusUserRoleType) => {
    if (radiusUserRole === "vpn" || radiusUserRole === "smartemsVpn") {
        return true;
    }

    return false;
};

const roleVpnEndpointDevicesDisabled: CheckboxProps["disabled"] = (values, touched, errors, name) => {
    const nameParts = name.split(".");
    nameParts.pop();

    const radiusUserRolePath = nameParts.join(".") + ".radiusUserRole";
    const radiusUserRole = getIn(values, radiusUserRolePath);

    if (roleVpnEndpointDevicesSupported(radiusUserRole)) {
        return false;
    }

    return true;
};

const fields = {
    radiusEnabled: <RadioFalseTrue {...{ required: true, help: true }} />,
    radiusAuth: <RadioEnum {...{ ...showAndRequireOnTrue("radiusEnabled"), enum: radiusAuthenticationProtocol }} />,
    radiusServer: <Text {...{ ...showAndRequireOnTrue("radiusEnabled") }} />,
    radiusSecret: <TextWithObfuscatedValue {...{ ...showAndRequireOnTrue("radiusEnabled") }} />,
    radiusNasAddress: <Text {...{ ...showAndRequireOnTrue("radiusEnabled") }} />,
    radiusNasPort: <Text {...{ ...showAndRequireOnTrue("radiusEnabled") }} />,
    radiusWelotecGroupMappingEnabled: <RadioFalseTrue {...{ ...showOnTrue("radiusEnabled"), help: true }} />,
    radiusWelotecTagMappingEnabled: (
        <RadioFalseTrue
            {...{
                hidden: (values: FormikValues) =>
                    values?.["radiusEnabled"] && values?.["radiusWelotecGroupMappingEnabled"] ? false : true,
                help: true,
            }}
        />
    ),
    radiusWelotecGroupMappings: (
        <Collection
            {...{
                hidden: (values: FormikValues) =>
                    values?.["radiusEnabled"] && values?.["radiusWelotecGroupMappingEnabled"] ? false : true,
                fields: {
                    name: <Text {...{ required: true }} />,
                    radiusUserRole: (
                        <SelectEnum
                            required
                            enum={radiusUserRole}
                            onChange={(path, setFieldValue, value, onChange) => {
                                onChange();

                                const nameParts = path.split(".");
                                nameParts.pop();
                                const namePrefix = nameParts.join(".");

                                // eslint-disable-next-line
                                const radiusUserRole = (value as any)?.id as RadiusUserRoleType;

                                if (!roleVpnEndpointDevicesSupported(radiusUserRole)) {
                                    const roleVpnEndpointDevicesPath = namePrefix + ".roleVpnEndpointDevices";
                                    setFieldValue(roleVpnEndpointDevicesPath, false);
                                }
                            }}
                        />
                    ),
                    roleVpnEndpointDevices: <Checkbox disabled={roleVpnEndpointDevicesDisabled} />,
                },
            }}
        />
    ),
};

export default getFields(fields);
