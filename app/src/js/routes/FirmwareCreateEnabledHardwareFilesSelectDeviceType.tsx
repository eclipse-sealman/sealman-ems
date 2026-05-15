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
import { MemoryOutlined } from "@mui/icons-material";
import SelectDeviceType from "~app/components/Page/SelectDeviceType";

const FirmwareCreateEnabledHardwareFilesSelectDeviceType = () => {
    return (
        <SelectDeviceType
            {...{
                title: "route.title.firmware",
                titleTo: "/firmware/list",
                subtitle: "route.subtitle.create",
                hint: "route.hint.selectDeviceType",
                icon: <MemoryOutlined />,
                emptyAlert: "firmwareCreateEnabledHardwareFilesSelectDeviceType.noResults",
                endpoint: "/options/available/firmwarehardware/device/types",
                to: (deviceType) => "/firmware/create/enabledhardwarefiles/" + deviceType.id,
            }}
        />
    );
};

export default FirmwareCreateEnabledHardwareFilesSelectDeviceType;
