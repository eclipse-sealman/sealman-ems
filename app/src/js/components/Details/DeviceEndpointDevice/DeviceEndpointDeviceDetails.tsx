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
import { Box } from "@mui/material";
import DisplaySurface from "~app/components/Common/DisplaySurface";
import { DeviceEndpointDeviceInterface } from "~app/entities/DeviceEndpointDevice/definitions";
import DeviceEndpointDeviceDetailsDisplay from "~app/components/Details/DeviceEndpointDevice/DeviceEndpointDeviceDetailsDisplay";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";
import TableVpnLogs from "~app/components/Details/DeviceEndpointDevice/TableVpnLogs";

interface DeviceEndpointDeviceDetailsProps {
    deviceEndpointDevice: DeviceEndpointDeviceInterface;
}

const DeviceEndpointDeviceDetails = ({ deviceEndpointDevice }: DeviceEndpointDeviceDetailsProps) => {
    return (
        <>
            <Box {...{ mb: 2 }}>
                <DisplaySurface
                    {...{
                        title: "deviceEndpointDeviceDetails.details",
                    }}
                >
                    <DeviceEndpointDeviceDetailsDisplay {...{ deviceEndpointDevice }} />
                </DisplaySurface>
            </Box>
            <Box {...{ sx: { mb: 2 } }}>
                <SurfaceTitle {...{ title: "deviceEndpointDeviceDetails.vpnLogs", disableBackButton: true }} />
                <TableVpnLogs {...{ deviceEndpointDevice }} />
            </Box>
        </>
    );
};

export default DeviceEndpointDeviceDetails;
export { DeviceEndpointDeviceDetailsProps };
