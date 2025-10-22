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
import { Text, getFields, Checkbox, MultiselectApi, DateTimePicker } from "@arteneo/forge";
import PasswordComplexityHelp from "~app/components/Form/fields/PasswordComplexityHelp";
import { showAndRequireOnFalse, showOn, showOnFalse, showOnTrue } from "~app/utilities/fields";
import UserCertificateAutomaticBehaviorCollection from "~app/components/Form/fields/UserCertificateAutomaticBehaviorCollection";
import PasswordWithObfuscatedValue from "~app/components/Form/fields/PasswordWithObfuscatedValue";

const fields = {
    username: <Text {...{ required: true }} />,
    plainPassword: <PasswordComplexityHelp {...{ required: true }} />,
    plainPasswordRepeat: <PasswordWithObfuscatedValue {...{ required: true }} />,
    roleAdmin: (
        <Checkbox
            {...{
                onChange: (path, setFieldValue, event, checked, onChange) => {
                    onChange();

                    setFieldValue("roleSmartems", false);
                    setFieldValue("roleVpn", false);
                    setFieldValue("roleVpnEndpointDevices", false);
                    setFieldValue("accessTags", []);
                },
            }}
        />
    ),
    roleSmartems: <Checkbox {...{ ...showOnFalse("roleAdmin") }} />,
    roleVpn: <Checkbox {...{ ...showOnFalse("roleAdmin") }} />,
    roleVpnEndpointDevices: <Checkbox {...{ ...showOn({ roleAdmin: false, roleVpn: true }) }} />,
    accessTags: <MultiselectApi {...{ endpoint: "/options/access/tags", ...showAndRequireOnFalse("roleAdmin") }} />,
    enabled: (
        <Checkbox
            {...{
                onChange: (path, setFieldValue, event, checked, onChange) => {
                    onChange();

                    setFieldValue("enabledExpireAt", null);
                },
            }}
        />
    ),
    enabledExpireAt: <DateTimePicker {...{ help: true, ...showOnTrue("enabled") }} />,
    certificateBehaviours: <UserCertificateAutomaticBehaviorCollection />,
    disablePasswordExpire: <Checkbox />,
    totpEnabled: <Checkbox {...{ help: true }} />,
};

export default getFields(fields);
