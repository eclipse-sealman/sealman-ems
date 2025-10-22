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
import { useNavigate } from "react-router-dom";
import Display from "~app/components/Display/Display";
import getRows from "~app/entities/DeviceEndpointDevice/rows";
import ResultEdit from "~app/components/Table/actions/ResultEdit";
import { DeviceEndpointDeviceInterface } from "~app/entities/DeviceEndpointDevice/definitions";
import ResultDelete from "~app/components/Table/actions/ResultDelete";
import ResultVpnOpenConnection from "~app/components/Table/actions/ResultVpnOpenConnection";
import { useUser } from "~app/contexts/User";
import VpnCloseOwnedConnection from "~app/entities/DeviceEndpointDevice/actions/VpnCloseOwnedConnection";

interface DeviceEndpointDeviceDetailsDisplayProps {
    deviceEndpointDevice: DeviceEndpointDeviceInterface;
}

const DeviceEndpointDeviceDetailsDisplay = ({ deviceEndpointDevice }: DeviceEndpointDeviceDetailsDisplayProps) => {
    const { isAccessGranted } = useUser();

    const navigate = useNavigate();

    const rows = getRows(undefined, !isAccessGranted({ admin: true }) ? ["accessTags"] : undefined);

    return (
        <>
            <Display
                {...{
                    result: deviceEndpointDevice,
                    rows,
                }}
            />
            <Box {...{ sx: { display: "flex", justifyContent: "space-between", gap: 2, mt: 2 } }}>
                <Box
                    {...{
                        sx: {
                            display: "flex",
                            flexWrap: "wrap",
                            alignItems: "center",
                            justifyContent: "flex-end",
                            gap: 1,
                        },
                    }}
                >
                    <ResultDelete
                        {...{
                            denyBehavior: "hide",
                            result: deviceEndpointDevice,
                            dialogProps: (result) => ({
                                confirmProps: {
                                    endpoint: "/deviceendpointdevice/" + result.id,
                                    onSuccess: (defaultOnSuccess) => {
                                        defaultOnSuccess();
                                        navigate("/device/details/" + deviceEndpointDevice.device?.id);
                                    },
                                },
                            }),
                        }}
                    />
                </Box>
                <Box
                    {...{
                        sx: {
                            display: "flex",
                            flexWrap: "wrap",
                            alignItems: "center",
                            justifyContent: "flex-end",
                            gap: 1,
                        },
                    }}
                >
                    <ResultVpnOpenConnection
                        {...{ result: deviceEndpointDevice, entityPrefix: "deviceendpointdevice" }}
                    />
                    <VpnCloseOwnedConnection {...{ result: deviceEndpointDevice }} />
                    <ResultEdit
                        {...{
                            result: deviceEndpointDevice,
                            to: (result) => "/deviceendpointdevice/edit/" + result.id,
                        }}
                    />
                </Box>
            </Box>
        </>
    );
};

export default DeviceEndpointDeviceDetailsDisplay;
export { DeviceEndpointDeviceDetailsDisplayProps };
