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
import { LinkOutlined } from "@mui/icons-material";
import getColumns from "~app/entities/VpnConnection/columns";
import getFilters from "~app/entities/VpnConnection/filters";
import Builder from "~app/components/Crud/Builder";
import { useUser } from "~app/contexts/User";

const VpnConnection = () => {
    const { isAccessGranted } = useUser();
    const columns = getColumns();
    const filters = getFilters(undefined, !isAccessGranted({ admin: true }) ? ["user"] : undefined);

    return (
        <Builder
            {...{
                endpointPrefix: "/vpnconnection",
                title: "route.title.vpnConnection",
                icon: <LinkOutlined />,
                listProps: {
                    columns,
                    filters,
                    defaultSorting: {
                        createdAt: "desc",
                    },
                },
                deleteProps: {},
                duplicateProps: {},
            }}
        />
    );
};

export default VpnConnection;
