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
import { OptionInterface } from "@arteneo/forge";
import { MemoryOutlined } from "@mui/icons-material";
import { DeviceTypeInterface } from "~app/entities/DeviceType/definitions";
import SelectTiles from "~app/components/Page/SelectTiles";
import Tile from "~app/components/Common/Tile";
import { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";
import useEndpoint from "~app/hooks/useEndpoint";

const FirmwareCreateSelectFeature = () => {
    const { deviceTypeId } = useParams();
    const navigate = useNavigate();

    const { object: deviceType, loading } = useEndpoint<DeviceTypeInterface>("/options/devicetype/" + deviceTypeId);
    const [tiles, setTiles] = React.useState<undefined | OptionInterface[]>(undefined);

    React.useEffect(() => {
        if (typeof deviceType === "undefined") {
            setTiles(undefined);
            return;
        }

        const tiles: OptionInterface[] = [];

        if (deviceType.hasFirmware1) {
            tiles.push({
                id: "1",
                representation: deviceType.nameFirmware1 ?? "",
            });
        }
        if (deviceType.hasFirmware2) {
            tiles.push({
                id: "2",
                representation: deviceType.nameFirmware2 ?? "",
            });
        }
        if (deviceType.hasFirmware3) {
            tiles.push({
                id: "3",
                representation: deviceType.nameFirmware3 ?? "",
            });
        }

        if (tiles.length === 1) {
            navigate("/firmware/create/" + deviceTypeId + "/" + tiles[0].id, { replace: true });
            return;
        }

        setTiles(tiles);
    }, [deviceTypeId, loading]);

    const titleProps: SurfaceTitleProps = {
        title: "route.title.firmware",
        titleTo: "/firmware/list",
        subtitle: "route.subtitle.create",
        icon: <MemoryOutlined />,
    };

    if (typeof tiles !== "undefined") {
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
                            to: "/firmware/create/" + deviceTypeId + "/" + option.id,
                            icon: <MemoryOutlined />,
                        }}
                    />
                ),
            }}
        />
    );
};

export default FirmwareCreateSelectFeature;
