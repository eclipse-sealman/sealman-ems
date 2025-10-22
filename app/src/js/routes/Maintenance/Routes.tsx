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
import { Route, Routes as RouterRoutes } from "react-router-dom";
import Dashboard from "~app/routes/Maintenance/Dashboard";
import Jobs from "~app/routes/Maintenance/Jobs";
import Logs from "~app/routes/Maintenance/Logs";
import MaintenanceSchedule from "~app/routes/Maintenance/MaintenanceSchedule";
import BackupUpload from "~app/routes/Maintenance/BackupUpload";
import BackupCreate from "~app/routes/Maintenance/BackupCreate";
import RestoreCreate from "~app/routes/Maintenance/RestoreCreate";
import BackupForUpdateCreate from "~app/routes/Maintenance/BackupForUpdateCreate";
import MaintenanceMode from "~app/routes/Maintenance/MaintenanceMode";
import TriggerError404 from "~app/components/TriggerError404";

const Routes = () => {
    return (
        <RouterRoutes>
            <Route
                {...{
                    path: "/dashboard",
                    element: <Dashboard />,
                }}
            />
            <Route
                {...{
                    path: "/jobs",
                    element: <Jobs />,
                }}
            />
            <Route
                {...{
                    path: "/logs",
                    element: <Logs />,
                }}
            />
            <Route
                {...{
                    path: "/maintenanceschedule/*",
                    element: <MaintenanceSchedule />,
                }}
            />
            <Route
                {...{
                    path: "/backup/upload",
                    element: <BackupUpload />,
                }}
            />
            <Route
                {...{
                    path: "/backup/create",
                    element: <BackupCreate />,
                }}
            />
            <Route
                {...{
                    path: "/backupforupdate/create",
                    element: <BackupForUpdateCreate />,
                }}
            />
            <Route
                {...{
                    path: "/restore/create",
                    element: <RestoreCreate />,
                }}
            />
            <Route
                {...{
                    path: "/maintenancemode",
                    element: <MaintenanceMode />,
                }}
            />
            <Route path="*" element={<TriggerError404 />} />
        </RouterRoutes>
    );
};

export default Routes;
