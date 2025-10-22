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
import { RouterOutlined } from "@mui/icons-material";
import SelectDeviceType from "~app/components/Page/SelectDeviceType";

const DeviceCreateSelectDeviceType = () => {
    return (
        <SelectDeviceType
            {...{
                title: "route.title.device",
                titleTo: "/device/list",
                subtitle: "route.subtitle.create",
                hint: "route.hint.selectDeviceType",
                icon: <RouterOutlined />,
                to: (deviceType) => "/device/create/" + deviceType.id,
            }}
        />
    );
};

export default DeviceCreateSelectDeviceType;
