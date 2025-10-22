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
import { DeviceInterface } from "~app/entities/Device/definitions";
import DeviceDetailsDisplay from "~app/components/Details/Device/DeviceDetailsDisplay";
import DeviceDefinedVariablesDisplay from "~app/components/Details/Device/DeviceDefinedVariablesDisplay";
import DevicePredefinedVariablesDisplay from "~app/components/Details/Device/DevicePredefinedVariablesDisplay";
import TableEndpointDevices from "~app/components/Details/Device/TableEndpointDevices";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";
import TableVpnLogs from "~app/components/Details/Device/TableVpnLogs";
import TableCommunicationLogs from "~app/components/Details/Device/TableCommunicationLogs";
import TableConfigLogs from "~app/components/Details/Device/TableConfigLogs";
import TableDiagnoseLogs from "~app/components/Details/Device/TableDiagnoseLogs";
import { useUser } from "~app/contexts/User";
import TableDeviceCommands from "~app/components/Details/Device/TableDeviceCommands";
import DeviceCertificateDetails from "~app/components/Details/Device/DeviceCertificateDetails";
import TableDeviceSecrets from "~app/components/Details/Device/TableDeviceSecrets";

interface DeviceDetailsProps {
    device: DeviceInterface;
}

const DeviceDetails = ({ device }: DeviceDetailsProps) => {
    const { isAccessGranted } = useUser();

    const deviceType = device.deviceType;

    const hasVariables = isAccessGranted({ admin: true, smartems: true }) && deviceType.hasVariables;
    const hasConfig = deviceType.hasConfig1 || deviceType.hasConfig2 || deviceType.hasConfig3;

    return (
        <>
            <Box
                {...{
                    sx: {
                        display: "grid",
                        alignItems: "flex-start",
                        gridTemplateColumns: {
                            xs: "minmax(0, 1fr)",
                            lg: hasVariables ? "repeat(2, minmax(0,1fr))" : "1fr",
                        },
                        gap: { xs: 2, lg: 4 },
                        mb: 2,
                    },
                }}
            >
                <DisplaySurface
                    {...{
                        title: "deviceDetails.details",
                    }}
                >
                    <DeviceDetailsDisplay {...{ device }} />
                </DisplaySurface>
                {hasVariables && (
                    <Box
                        {...{
                            sx: {
                                display: "flex",
                                flexDirection: "column",
                                gap: { xs: 2, lg: 4 },
                            },
                        }}
                    >
                        <DisplaySurface
                            {...{
                                title: "deviceDetails.definedVariables",
                            }}
                        >
                            <DeviceDefinedVariablesDisplay {...{ device }} />
                        </DisplaySurface>
                        <DisplaySurface
                            {...{
                                title: "deviceDetails.predefinedVariables",
                            }}
                        >
                            <DevicePredefinedVariablesDisplay {...{ device }} />
                        </DisplaySurface>
                    </Box>
                )}
            </Box>

            {device?.hasDeviceSecrets && (
                <Box {...{ sx: { mb: 2 } }}>
                    <SurfaceTitle {...{ title: "deviceDetails.deviceSecrets", disableBackButton: true }} />
                    <TableDeviceSecrets {...{ device }} />
                </Box>
            )}

            {isAccessGranted({ adminVpn: true, vpn: true }) && deviceType.isEndpointDevicesAvailable && (
                <Box {...{ sx: { mb: 2 } }}>
                    <SurfaceTitle {...{ title: "deviceDetails.endpointDevices", disableBackButton: true }} />
                    <TableEndpointDevices {...{ device }} />
                </Box>
            )}

            {isAccessGranted({ admin: true, smartems: true }) && deviceType.hasCertificates && (
                <Box {...{ sx: { mb: 2 } }}>
                    <SurfaceTitle {...{ title: "deviceDetails.certificates", disableBackButton: true }} />
                    <DeviceCertificateDetails {...{ device }} />
                </Box>
            )}

            {isAccessGranted({ adminScep: true, vpn: true }) && deviceType.hasCertificates && (
                <Box {...{ sx: { mb: 2 } }}>
                    <SurfaceTitle {...{ title: "deviceDetails.vpnLogs", disableBackButton: true }} />
                    <TableVpnLogs {...{ device }} />
                </Box>
            )}

            {isAccessGranted({ admin: true, smartems: true }) && deviceType.communicationProcedure !== "none" && (
                <Box {...{ sx: { mb: 2 } }}>
                    <SurfaceTitle {...{ title: "deviceDetails.communicationLogs", disableBackButton: true }} />
                    <TableCommunicationLogs {...{ device }} />
                </Box>
            )}

            {isAccessGranted({ admin: true, smartems: true }) && deviceType.hasDeviceCommands && (
                <Box {...{ sx: { mb: 2 } }}>
                    <SurfaceTitle {...{ title: "deviceDetails.deviceCommands", disableBackButton: true }} />
                    <TableDeviceCommands {...{ device }} />
                </Box>
            )}

            {isAccessGranted({ admin: true, smartems: true }) && hasConfig && (
                <Box {...{ sx: { mb: 2 } }}>
                    <SurfaceTitle {...{ title: "deviceDetails.configLogs", disableBackButton: true }} />
                    <TableConfigLogs {...{ device }} />
                </Box>
            )}

            {isAccessGranted({ admin: true, smartems: true }) && deviceType.hasRequestDiagnose && (
                <Box {...{ sx: { mb: 2 } }}>
                    <SurfaceTitle {...{ title: "deviceDetails.diagnoseLogs", disableBackButton: true }} />
                    <TableDiagnoseLogs {...{ device }} />
                </Box>
            )}
        </>
    );
};

export default DeviceDetails;
export { DeviceDetailsProps };
