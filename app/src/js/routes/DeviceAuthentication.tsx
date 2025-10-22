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
import { KeyOutlined } from "@mui/icons-material";
import getColumns from "~app/entities/DeviceAuthentication/columns";
import getFilters from "~app/entities/DeviceAuthentication/filters";
import getFields from "~app/entities/DeviceAuthentication/fields";
import Builder from "~app/components/Crud/Builder";

const DeviceAuthentication = () => {
    const columns = getColumns();
    const filters = getFilters();

    const fields = getFields();

    return (
        <Builder
            {...{
                endpointPrefix: "/deviceauthentication",
                title: "route.title.deviceAuthentication",
                icon: <KeyOutlined />,
                listProps: {
                    columns,
                    filters,
                },
                createProps: {
                    fields: fields,
                },
                editProps: {
                    fields: fields,
                },
            }}
        />
    );
};

export default DeviceAuthentication;
