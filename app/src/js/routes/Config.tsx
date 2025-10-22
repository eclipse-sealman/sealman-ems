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
import { SettingsOutlined } from "@mui/icons-material";
import getColumns from "~app/entities/Config/columns";
import getFilters from "~app/entities/Config/filters";
import Builder from "~app/components/Crud/Builder";
import { useUser } from "~app/contexts/User";

const Config = () => {
    const { isAccessGranted } = useUser();
    const columns = getColumns();
    const filters = getFilters(undefined, !isAccessGranted({ admin: true }) ? ["updatedBy", "createdBy"] : undefined);

    return (
        <Builder
            {...{
                endpointPrefix: "/config",
                title: "route.title.config",
                icon: <SettingsOutlined />,
                listProps: {
                    columns,
                    filters,
                    hasCreate: true,
                    hasEdit: true,
                    hasDuplicate: true,
                    hasExportCsv: true,
                    hasExportExcel: true,
                    defaultSorting: {
                        createdAt: "desc",
                    },
                },
                deleteProps: {},
            }}
        />
    );
};

export default Config;
