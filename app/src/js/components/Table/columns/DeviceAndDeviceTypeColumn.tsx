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
import { ColumnPathInterface } from "@arteneo/forge";
import { getIn } from "formik";

interface DeviceAndDeviceTypeColumnProps extends ColumnPathInterface {
    deviceTypePath?: string;
}

const DeviceAndDeviceTypeColumn = ({
    result,
    columnName,
    path,
    deviceTypePath = "deviceType",
}: DeviceAndDeviceTypeColumnProps) => {
    if (typeof columnName === "undefined") {
        throw new Error("DeviceAndDeviceTypeColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("DeviceAndDeviceTypeColumn component: Missing required result prop");
    }

    const device = getIn(result, path ? path : columnName);
    if (!device) {
        return null;
    }

    const deviceType = getIn(device, deviceTypePath);

    return (
        <>
            {device.representation}
            {deviceType && <> ({deviceType.representation})</>}
        </>
    );
};

export default DeviceAndDeviceTypeColumn;
export { ColumnPathInterface as DeviceAndDeviceTypeColumnProps };
