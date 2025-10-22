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
import { OptionInterface } from "@arteneo/forge";
import {
    RouterOutlined,
    Api,
    CloudSync,
    Hub,
    LinkOff,
    Router,
    SettingsInputAntenna,
    Computer,
    Share,
    AdminPanelSettings,
    Devices,
} from "@mui/icons-material";
import Tile from "~app/components/Common/Tile";
import SelectEndpointTiles from "~app/components/Page/SelectEndpointTiles";

const DeviceTypeCreateSelectCommunicationProcedure = () => {
    const renderIcon = (icon?: string) => {
        switch (icon) {
            case "none":
                return <LinkOff />;
            case "noneScep":
                return <AdminPanelSettings />;
            case "noneVpn":
                return <Share />;
            case "routerOneConfig":
                return <Router />;
            case "router":
                return <Router />;
            case "routerDsa":
                return <Router />;
            case "restApi":
                return <Api />;
            case "edgeGateway":
                return <Computer />;
            case "edgeGatewayWithVpnContainerClient":
                return <Devices />;
            case "flexEdge":
                return <Hub />;
            case "sgGateway":
                return <SettingsInputAntenna />;
            case "vpnContainerClient":
                return <CloudSync />;
            default:
                return <CloudSync />;
        }
    };

    return (
        <SelectEndpointTiles<OptionInterface>
            {...{
                title: "route.title.configuration.deviceType",
                subtitle: "route.subtitle.create",
                hint: "route.hint.selectCommunicationProcedure",
                icon: <RouterOutlined />,
                endpoint: "/options/communication/procedures",
                renderTile: (option) => (
                    <Tile
                        key={option.id}
                        {...{
                            title: option.representation,
                            to: "/configuration/devicetype/create/" + option.id,
                            icon: renderIcon(option.id as string),
                        }}
                    />
                ),
            }}
        />
    );
};

export default DeviceTypeCreateSelectCommunicationProcedure;
