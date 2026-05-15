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
import { getFields, RadioEnum, Text, Textarea } from "@arteneo/forge";
import MultiselectDeviceTypeApi from "~app/components/Form/fields/MultiselectDeviceTypeApi";
import { crlType } from "~app/entities/DeviceMTlsAuthentication/enums";
import { showAndRequireOnEqual } from "~app/utilities/fields";

const fields = {
    name: <Text {...{ required: true }} />,
    deviceTypes: <MultiselectDeviceTypeApi {...{ endpoint: "/options/device/types", label: "deviceTypes" }} />,
    certificateCa: <Textarea {...{ required: true, help: true }} />,
    crlType: <RadioEnum {...{ enum: crlType, required: true, help: true }} />,
    certificateCrl: <Textarea {...{ ...showAndRequireOnEqual("crlType", "pem"), help: true }} />,
    certificateCrlUrl: <Text {...{ ...showAndRequireOnEqual("crlType", "url"), help: true }} />,
};

export default getFields(fields);
