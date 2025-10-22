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
import { RouterOutlined, SettingsOutlined } from "@mui/icons-material";
import { Box } from "@mui/material";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";
import Tile from "~app/components/Common/Tile";
import { generalConfiguration } from "~app/routes/Configuration/General";
import { logsConfiguration } from "~app/routes/Configuration/Logs";
import { radiusConfiguration } from "~app/routes/Configuration/Radius";
import { totpConfiguration } from "~app/routes/Configuration/Totp";
import { ssoConfiguration } from "~app/routes/Configuration/Sso";
import { vpnConfiguration } from "~app/routes/Configuration/Vpn";
import { documentationConfiguration } from "~app/routes/Configuration/Documentation";
import DashboardTileInterface from "~app/definitions/DashboardTileInterface";
import { useUser } from "~app/contexts/User";
import { certificateTypeConfiguration } from "~app/routes/Configuration/CertificateType";

const Dashboard = () => {
    const { isAccessGranted } = useUser();

    const titlePrefix = "route.title.configuration.";
    const toPrefix = "/configuration";

    const tiles: DashboardTileInterface[] = [
        generalConfiguration,
        {
            title: "deviceType",
            to: "/devicetype/list",
            icon: <RouterOutlined />,
        },
        logsConfiguration,
        radiusConfiguration,
        totpConfiguration,
        ssoConfiguration,
    ];

    if (isAccessGranted({ adminVpn: true })) {
        tiles.push(vpnConfiguration);
    }

    //CertificateType can be setup (even SCEP settings) regardless of license, but without required license CertificateType will not be available for use
    tiles.push(certificateTypeConfiguration);
    tiles.push(documentationConfiguration);

    return (
        <>
            <Box {...{ mb: 1 }}>
                <SurfaceTitle {...{ title: "route.title.configuration.dashboard", icon: <SettingsOutlined /> }} />
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
