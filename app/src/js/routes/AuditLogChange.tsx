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
import { HistoryOutlined } from "@mui/icons-material";
import getColumns from "~app/entities/AuditLogChange/columns";
import getFilters from "~app/entities/AuditLogChange/filters";
import Builder from "~app/components/Crud/Builder";

const AuditLogChange = () => {
    const columns = getColumns();
    const filters = getFilters();

    return (
        <Builder
            {...{
                endpointPrefix: "/auditlogchange",
                title: "route.title.auditLogChange",
                icon: <HistoryOutlined />,
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

export default AuditLogChange;
