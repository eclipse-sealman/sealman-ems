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
import { VpnKeyOutlined } from "@mui/icons-material";
import getFields from "~app/entities/DeviceSecret/fields";
import { Route, Routes } from "react-router-dom";
import TriggerError404 from "~app/components/TriggerError404";
import Edit from "~app/entities/DeviceSecret/Crud/Edit";
import Create from "~app/entities/DeviceSecret/Crud/Create";

const DeviceSecret = () => {
    const fields = getFields();

    const crudProps = {
        endpointPrefix: "/devicesecret",
        fields: fields,
        titleProps: {
            title: "route.title.deviceSecret",
            icon: <VpnKeyOutlined />,
        },
    };
    return (
        <Routes>
            <Route
                {...{
                    path: "/create/:deviceId/:deviceTypeSecretId",
                    element: <Create {...crudProps} />,
                }}
            />
            <Route
                {...{
                    path: "/edit/:id",
                    element: <Edit {...crudProps} />,
                }}
            />
            <Route path="*" element={<TriggerError404 />} />
        </Routes>
    );
};

export default DeviceSecret;
