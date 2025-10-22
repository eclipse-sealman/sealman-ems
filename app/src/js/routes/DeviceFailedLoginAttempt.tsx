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
import { LoginOutlined } from "@mui/icons-material";
import getColumns from "~app/entities/DeviceFailedLoginAttempt/columns";
import getFilters from "~app/entities/DeviceFailedLoginAttempt/filters";
import Builder from "~app/components/Crud/Builder";

const DeviceFailedLoginAttempt = () => {
    const columns = getColumns();
    const filters = getFilters();

    return (
        <Builder
            {...{
                endpointPrefix: "/devicefailedloginattempt",
                title: "route.title.deviceFailedLoginAttempt",
                icon: <LoginOutlined />,
                listProps: {
                    columns,
                    filters,
                    hasDelete: false,
                    defaultSorting: {
                        createdAt: "desc",
                    },
                },
            }}
        />
    );
};

export default DeviceFailedLoginAttempt;
