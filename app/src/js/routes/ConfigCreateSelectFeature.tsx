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
import { useNavigate, useParams } from "react-router-dom";
import axios from "axios";
import { AXIOS_CANCELLED_UNMOUNTED, OptionInterface, useHandleCatch, useLoader } from "@arteneo/forge";
import { SettingsOutlined } from "@mui/icons-material";
import { DeviceTypeInterface } from "~app/entities/DeviceType/definitions";
import SelectTiles from "~app/components/Page/SelectTiles";
import Tile from "~app/components/Common/Tile";
import { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";

const ConfigCreateSelectFeature = () => {
    const { deviceTypeId } = useParams();
    const navigate = useNavigate();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [deviceType, setDeviceType] = React.useState<undefined | DeviceTypeInterface>(undefined);
    const [tiles, setTiles] = React.useState<OptionInterface[]>([]);

    React.useEffect(() => load(), [deviceTypeId]);

    const getTiles = (deviceType: DeviceTypeInterface): OptionInterface[] => {
        const tiles: OptionInterface[] = [];

        if (typeof deviceType !== "undefined") {
            if (deviceType.hasConfig1) {
                tiles.push({
                    id: "1",
                    representation: deviceType.nameConfig1 ?? "",
                });
            }
            if (deviceType.hasConfig2) {
                tiles.push({
                    id: "2",
                    representation: deviceType.nameConfig2 ?? "",
                });
            }
            if (deviceType.hasConfig3) {
                tiles.push({
                    id: "3",
                    representation: deviceType.nameConfig3 ?? "",
                });
            }
        }

        return tiles;
    };

    const load = () => {
        showLoader();

        const axiosSource = axios.CancelToken.source();

        axios
            .request({ url: "/options/devicetype/" + deviceTypeId, cancelToken: axiosSource.token })
            .then((response) => {
                const deviceType: DeviceTypeInterface = response.data;
                const tiles = getTiles(deviceType);
                if (tiles.length === 1) {
                    navigate("/config/create/" + deviceTypeId + "/" + tiles[0].id, { replace: true });
                    return;
                }

                setDeviceType(deviceType);
                setTiles(tiles);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });

        return () => {
            axiosSource.cancel(AXIOS_CANCELLED_UNMOUNTED);
        };
    };

    const titleProps: SurfaceTitleProps = {
        title: "route.title.config",
        titleTo: "/config/list",
        subtitle: "route.subtitle.create",
        icon: <SettingsOutlined />,
    };

    if (deviceType?.name) {
        titleProps.hint = "route.hint.selectFeature";
        titleProps.hintVariables = { deviceType: deviceType?.name };
    }

    return (
        <SelectTiles<OptionInterface>
            {...{
                ...titleProps,
                tiles,
                renderTile: (option) => (
                    <Tile
                        key={option.id}
                        {...{
                            title: option.representation,
                            disableTranslate: true,
                            to: "/config/create/" + deviceTypeId + "/" + option.id,
                            icon: <SettingsOutlined />,
                        }}
                    />
                ),
            }}
        />
    );
};

export default ConfigCreateSelectFeature;
