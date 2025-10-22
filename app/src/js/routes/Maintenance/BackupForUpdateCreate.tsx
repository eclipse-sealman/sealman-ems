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
import { SystemUpdateAltOutlined } from "@mui/icons-material";
import { Form } from "@arteneo/forge";
import { useNavigate } from "react-router-dom";
import DashboardTileInterface from "~app/definitions/DashboardTileInterface";
import MaintenanceConfirmationFieldset from "~app/fieldsets/MaintenanceConfirmationFieldset";
import getFields from "~app/entities/Maintenance/backupForUpdateCreateFields";
import Surface from "~app/components/Common/Surface";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";

const backupForUpdateCreateConfiguration: DashboardTileInterface = {
    title: "backupForUpdateCreate",
    to: "/backupforupdate/create",
    icon: <SystemUpdateAltOutlined />,
};

const BackupForUpdateCreate = () => {
    const navigate = useNavigate();

    const fields = getFields();

    return (
        <>
            <SurfaceTitle
                {...{
                    title: "route.title.maintenance.dashboard",
                    titleTo: "/maintenance/dashboard",
                    subtitle: "route.title.maintenance." + backupForUpdateCreateConfiguration.title,
                    icon: backupForUpdateCreateConfiguration.icon,
                }}
            />
            <Surface>
                <Form
                    {...{
                        endpoint: "/maintenance/create",
                        children: (
                            <MaintenanceConfirmationFieldset
                                {...{
                                    fields,
                                    label: "maintenance.confirmation.backupForUpdate.info",
                                    labelWarning: "maintenance.confirmation.backupForUpdate.warning",
                                    backButtonProps: { onClick: () => navigate("/maintenance/dashboard") },
                                }}
                            />
                        ),
                        changeSubmitValues: (values) => {
                            delete values.backupForUpdateConfirmation;

                            values["type"] = "backupForUpdate";
                            return values;
                        },
                        onSubmitSuccess: (defaultOnSubmitSuccess) => {
                            defaultOnSubmitSuccess();
                            navigate("/maintenance/jobs");
                        },
                        fields,
                    }}
                />
            </Surface>
        </>
    );
};

export default BackupForUpdateCreate;
export { backupForUpdateCreateConfiguration };
