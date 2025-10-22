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
import SurfaceTitle from "~app/components/Common/SurfaceTitle";
import DashboardTileInterface from "~app/definitions/DashboardTileInterface";
import getColumns from "~app/entities/MaintenanceLog/columns";
import getFilters from "~app/entities/MaintenanceLog/filters";
import Table from "~app/components/Table/components/Table";

const logsConfiguration: DashboardTileInterface = {
    title: "logs",
    to: "/logs",
    icon: <HistoryOutlined />,
};

const Logs = () => {
    const columns = getColumns();
    const filters = getFilters();

    return (
        <>
            <SurfaceTitle
                {...{
                    title: "route.title.maintenance.dashboard",
                    titleTo: "/maintenance/dashboard",
                    subtitle: "route.title.maintenance." + logsConfiguration.title,
                    icon: logsConfiguration.icon,
                }}
            />
            <Table
                {...{
                    endpoint: "/maintenancelog/list",
                    columns,
                    filters,
                    defaultSorting: {
                        createdAt: "desc",
                    },
                    additionalSorting: {
                        id: "desc",
                    },
                }}
            />
        </>
    );
};

export default Logs;
export { logsConfiguration };
