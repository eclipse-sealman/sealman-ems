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
import { EventRepeatOutlined } from "@mui/icons-material";
import DashboardTileInterface from "~app/definitions/DashboardTileInterface";
import getFilters from "~app/entities/MaintenanceSchedule/filters";
import getColumns from "~app/entities/MaintenanceSchedule/columns";
import getFields from "~app/entities/MaintenanceSchedule/fields";
import Builder from "~app/components/Crud/Builder";

const maintenanceScheduleConfiguration: DashboardTileInterface = {
    title: "maintenanceSchedule",
    to: "/maintenanceschedule/list",
    icon: <EventRepeatOutlined />,
};

const MaintenanceSchedule = () => {
    const columns = getColumns();
    const filters = getFilters();
    const fields = getFields();

    return (
        <Builder
            {...{
                endpointPrefix: "/maintenanceschedule",
                title: "route.title.maintenance." + maintenanceScheduleConfiguration.title,
                icon: maintenanceScheduleConfiguration.icon,
                listProps: {
                    columns,
                    filters,
                },
                createProps: {
                    fields,
                },
                editProps: {
                    fields,
                },
            }}
        />
    );
};

export default MaintenanceSchedule;
export { maintenanceScheduleConfiguration };
