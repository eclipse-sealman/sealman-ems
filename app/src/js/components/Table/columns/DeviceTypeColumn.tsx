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
import { getIn } from "formik";
import { ColumnPathInterface } from "@arteneo/forge";
import DeviceTypeIconRepresentation from "~app/components/Common/DeviceTypeIconRepresentation";

const DeviceTypeColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("DeviceTypeColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("DeviceTypeColumn component: Missing required result prop");
    }

    // Special case of path to use on device type list
    const value = path === "." ? result : getIn(result, path ? path : columnName);

    return (
        <DeviceTypeIconRepresentation
            representation={value?.representation}
            icon={value?.icon}
            color={value?.color}
            isAvailable={value?.isAvailable}
            enabled={value?.enabled}
        />
    );
};

export default DeviceTypeColumn;
export { ColumnPathInterface as DeviceTypeColumnProps };
