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
import { StorageOutlined } from "@mui/icons-material";
import { Form } from "@arteneo/forge";
import { useNavigate } from "react-router-dom";
import DashboardTileInterface from "~app/definitions/DashboardTileInterface";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import getFields from "~app/entities/Maintenance/backupCreateFields";
import Surface from "~app/components/Common/Surface";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";

const backupCreateConfiguration: DashboardTileInterface = {
    title: "backupCreate",
    to: "/backup/create",
    icon: <StorageOutlined />,
};

const BackupCreate = () => {
    const navigate = useNavigate();

    const fields = getFields();

    return (
        <>
            <SurfaceTitle
                {...{
                    title: "route.title.maintenance.dashboard",
                    titleTo: "/maintenance/dashboard",
                    subtitle: "route.title.maintenance." + backupCreateConfiguration.title,
                    icon: backupCreateConfiguration.icon,
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
                            values["type"] = "backup";
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

export default BackupCreate;
export { backupCreateConfiguration };
