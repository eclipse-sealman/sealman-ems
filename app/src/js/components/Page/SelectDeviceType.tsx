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
import { To } from "react-router-dom";
import Tile from "~app/components/Common/Tile";
import SelectEndpointTiles, { SelectEndpointTilesProps } from "~app/components/Page/SelectEndpointTiles";
import { DeviceTypeOptionInterface } from "~app/entities/DeviceType/definitions";
import { renderIcon } from "~app/components/Common/DeviceTypeIconRepresentation";

interface SelectDeviceTypeProps extends Partial<SelectEndpointTilesProps<DeviceTypeOptionInterface>> {
    to: (deviceType: DeviceTypeOptionInterface) => To;
}

const SelectDeviceType = ({ to, ...surfaceTitleProps }: SelectDeviceTypeProps) => {
    return (
        <SelectEndpointTiles<DeviceTypeOptionInterface>
            {...{
                endpoint: "/options/available/device/types",
                renderTile: (option) => (
                    <Tile
                        key={option.id}
                        {...{
                            title: option.representation,
                            disableTranslate: true,
                            to: to(option),
                            icon: renderIcon(option.icon, option.color),
                        }}
                    />
                ),
                ...surfaceTitleProps,
            }}
        />
    );
};

export default SelectDeviceType;
export { SelectDeviceTypeProps };
