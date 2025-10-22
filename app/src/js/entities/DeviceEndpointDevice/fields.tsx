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
import { Text, getFields, MultiselectApi, Textarea } from "@arteneo/forge";
import { FormikValues } from "formik";
import VirtualIpHostPart from "~app/components/Form/fields/VirtualIpHostPart";

const composeGetFields = (
    virtualSubnetCidr: number,
    endpointDevices: FormikValues[],
    virtualSubnetIpSortable?: number
) => {
    const fields = {
        name: <Text {...{ required: true }} />,
        physicalIp: <Text {...{ required: true }} />,
        virtualIpHostPart: (
            <VirtualIpHostPart
                {...{
                    required: true,
                    virtualSubnetIpSortable,
                    getVirtualSubnetCidr: () => virtualSubnetCidr,
                    getEndpointDevices: () => endpointDevices,
                }}
            />
        ),
        description: <Textarea {...{ fieldProps: { minRows: 1 } }} />,
        accessTags: <MultiselectApi {...{ endpoint: "/options/access/tags" }} />,
    };

    return getFields(fields);
};

export default composeGetFields;
