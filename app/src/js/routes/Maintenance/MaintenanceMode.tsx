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
import { ConstructionOutlined } from "@mui/icons-material";
import { Form } from "@arteneo/forge";
import { useNavigate } from "react-router-dom";
import DashboardTileInterface from "~app/definitions/DashboardTileInterface";
import MaintenanceConfirmationFieldset from "~app/fieldsets/MaintenanceConfirmationFieldset";
import getFields from "~app/entities/Maintenance/maintenanceModeFields";
import Surface from "~app/components/Common/Surface";
import { useConfiguration } from "~app/contexts/Configuration";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";

const maintenanceModeConfiguration: DashboardTileInterface = {
    title: "maintenanceMode",
    to: "/maintenancemode",
    icon: <ConstructionOutlined />,
};

const MaintenanceMode = () => {
    const navigate = useNavigate();
    const { reload, maintenanceMode } = useConfiguration();

    const fields = getFields(maintenanceMode ? ["maintenanceDisableConfirmation"] : ["maintenanceEnableConfirmation"]);

    return (
        <>
            <SurfaceTitle
                {...{
                    title: "route.title.maintenance.dashboard",
                    titleTo: "/maintenance/dashboard",
                    subtitle: "route.title.maintenance." + maintenanceModeConfiguration.title,
                    icon: maintenanceModeConfiguration.icon,
                }}
            />
            <Surface>
                <Form
                    {...{
                        endpoint: {
                            method: "get",
                            url: "/maintenance/mode/" + (maintenanceMode ? "disable" : "enable"),
                        },
                        children: (
                            <MaintenanceConfirmationFieldset
                                {...{
                                    fields,
                                    label: maintenanceMode
                                        ? "maintenance.confirmation.maintenanceModeDisable.info"
                                        : undefined,
                                    labelWarning: maintenanceMode
                                        ? undefined
                                        : "maintenance.confirmation.maintenanceModeEnable.warning",
                                    backButtonProps: { onClick: () => navigate("/maintenance/dashboard") },
                                }}
                            />
                        ),
                        changeSubmitValues: () => ({}),
                        onSubmitSuccess: (defaultOnSubmitSuccess) => {
                            reload();
                            defaultOnSubmitSuccess();
                            navigate("/maintenance/dashboard");
                        },
                        fields,
                    }}
                />
            </Surface>
        </>
    );
};

export default MaintenanceMode;
export { maintenanceModeConfiguration };
