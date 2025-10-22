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
import { BuildOutlined } from "@mui/icons-material";
import { Box } from "@mui/material";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";
import Tile from "~app/components/Common/Tile";
import { jobsConfiguration } from "~app/routes/Maintenance/Jobs";
import { logsConfiguration } from "~app/routes/Maintenance/Logs";
import { maintenanceScheduleConfiguration } from "~app/routes/Maintenance/MaintenanceSchedule";
import { backupUploadConfiguration } from "~app/routes/Maintenance/BackupUpload";
import { backupCreateConfiguration } from "~app/routes/Maintenance/BackupCreate";
import { restoreCreateConfiguration } from "~app/routes/Maintenance/RestoreCreate";
import { backupForUpdateCreateConfiguration } from "~app/routes/Maintenance/BackupForUpdateCreate";
import { maintenanceModeConfiguration } from "~app/routes/Maintenance/MaintenanceMode";
import DashboardTileInterface from "~app/definitions/DashboardTileInterface";

const Dashboard = () => {
    const titlePrefix = "route.title.maintenance.";
    const toPrefix = "/maintenance";

    const tiles: DashboardTileInterface[] = [
        jobsConfiguration,
        logsConfiguration,
        maintenanceScheduleConfiguration,
        backupUploadConfiguration,
        backupCreateConfiguration,
        restoreCreateConfiguration,
        backupForUpdateCreateConfiguration,
        maintenanceModeConfiguration,
    ];

    return (
        <>
            <Box {...{ mb: 1 }}>
                <SurfaceTitle {...{ title: "route.title.maintenance.dashboard", icon: <BuildOutlined /> }} />
            </Box>
            <Box
                {...{
                    sx: {
                        display: "grid",
                        gap: { xs: 2, lg: 4 },
                        gridTemplateColumns: {
                            xs: "minmax(0, 1fr)",
                            sm: "repeat(2, minmax(0,1fr))",
                            lg: "repeat(3, minmax(0,1fr))",
                        },
                    },
                }}
            >
                {tiles.map(({ title, to, icon }) => (
                    <Tile key={title} {...{ title: titlePrefix + title, to: toPrefix + to, icon }} />
                ))}
            </Box>
        </>
    );
};

export default Dashboard;
