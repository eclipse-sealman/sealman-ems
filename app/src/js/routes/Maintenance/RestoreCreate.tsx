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
import { SettingsBackupRestoreOutlined } from "@mui/icons-material";
import { Form } from "@arteneo/forge";
import { useNavigate } from "react-router-dom";
import DashboardTileInterface from "~app/definitions/DashboardTileInterface";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import getFields from "~app/entities/Maintenance/restoreCreateFields";
import Surface from "~app/components/Common/Surface";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";

const restoreCreateConfiguration: DashboardTileInterface = {
    title: "restoreCreate",
    to: "/restore/create",
    icon: <SettingsBackupRestoreOutlined />,
};

const RestoreCreate = () => {
    const navigate = useNavigate();

    const fields = getFields();

    return (
        <>
            <SurfaceTitle
                {...{
                    title: "route.title.maintenance.dashboard",
                    titleTo: "/maintenance/dashboard",
                    subtitle: "route.title.maintenance." + restoreCreateConfiguration.title,
                    icon: restoreCreateConfiguration.icon,
                }}
            />
            <Surface>
                <Form
                    {...{
                        endpoint: "/maintenance/create",
                        children: (
                            <CrudFieldset
                                {...{
                                    fields,
                                    backButtonProps: { onClick: () => navigate("/maintenance/dashboard") },
                                }}
                            />
                        ),
                        changeSubmitValues: (values) => {
                            values["type"] = "restore";
                            return values;
                        },
                        onSubmitSuccess: (defaultOnSubmitSuccess) => {
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

export default RestoreCreate;
export { restoreCreateConfiguration };
