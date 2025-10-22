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
import { getFields, RadioFalseTrue, Text } from "@arteneo/forge";
import { FormikValues } from "formik";
import TextareaWithClickEnable from "~app/components/Form/fields/TextareaWithClickEnable";
import TextWithObfuscatedValue from "~app/components/Form/fields/TextWithObfuscatedValue";

const fields = {
    vpnConnectionLimit: <RadioFalseTrue />,
    vpnConnectionDuration: (
        <Text
            {...{
                required: (values: FormikValues) => (values?.vpnConnectionLimit ? true : false),
                hidden: (values: FormikValues) => (values?.vpnConnectionLimit ? false : true),
                help: true,
            }}
        />
    ),
    opnsenseUrl: <Text {...{ required: true, help: true }} />,
    opnsenseApiKey: <Text {...{ help: true }} />,
    opnsenseApiSecret: <TextWithObfuscatedValue {...{ help: true }} />,
    opnsenseTimeout: <Text {...{ required: true, help: true }} />,
    verifyOpnsenseSslCertificate: <RadioFalseTrue />,
    devicesOpenvpnServerDescription: <Text {...{ required: true, help: true }} />,
    techniciansOpenvpnServerDescription: <Text {...{ required: true, help: true }} />,
    devicesVpnNetworks: <Text {...{ required: true, help: true }} />,
    devicesVpnNetworksRanges: <Text {...{ required: true, help: true }} />,
    devicesVirtualVpnNetworks: <Text {...{ required: true, help: true }} />,
    devicesVirtualVpnNetworksRanges: <Text {...{ required: true, help: true }} />,
    techniciansVpnNetworks: <Text {...{ required: true, help: true }} />,
    techniciansVpnNetworksRanges: <Text {...{ required: true, help: true }} />,
    devicesOvpnTemplate: (
        <TextareaWithClickEnable
            {...{ required: true, help: true, confirmationLabel: "configuration.vpn.devicesOvpnTemplate" }}
        />
    ),
    techniciansOvpnTemplate: (
        <TextareaWithClickEnable
            {...{ required: true, help: true, confirmationLabel: "configuration.vpn.techniciansOvpnTemplate" }}
        />
    ),
};

export default getFields(fields);
