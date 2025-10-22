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
import Display from "~app/components/Display/Display";
import { useNavigate } from "react-router-dom";
import composeGetRows, { composeGetTitleProps } from "~app/entities/Device/rows";
import ResultEdit from "~app/components/Table/actions/ResultEdit";
import { DeviceInterface } from "~app/entities/Device/definitions";
import ResultDelete from "~app/components/Table/actions/ResultDelete";
import ShowConfigExpand from "~app/entities/Device/actions/ShowConfigExpand";
import VpnExpand from "~app/entities/Device/actions/VpnExpand";
import { useUser } from "~app/contexts/User";
import CertificatesExpand from "~app/components/Table/actions/CertificatesExpand";

interface DeviceDetailsDisplayProps {
    device: DeviceInterface;
}

const DeviceDetailsDisplay = ({ device }: DeviceDetailsDisplayProps) => {
    const { isAccessGranted } = useUser();

    const navigate = useNavigate();

    const deviceType = device.deviceType;
    const getRows = composeGetRows(deviceType);

    const adminRowNames = ["uuid"];
    const adminVpnRowNames = ["virtualSubnetCidr", "masqueradeType"];

    const vpnRowNames = [
        "vpnConnected",
        "vpnIp",
        "virtualSubnet",
        "virtualIp",
        "vpnTrafficIn",
        "vpnTrafficOut",
        "vpnLastConnectionAt",
    ];

    const smartemsRowNames = [
        "accessTags",
        "staging",
        "connectionAmount",
        "reinstallFirmware1",
        "reinstallFirmware2",
        "reinstallFirmware3",
        "reinstallConfig1",
        "reinstallConfig2",
        "reinstallConfig3",
        "requestDiagnoseData",
        "requestConfigData",
        "commandRetryCount",
        "xForwardedFor",
        "host",
        "ipv6Prefix",
        "uptime",
        "cellId",
        "cellularIp1",
        "cellularUptime1",
        "cellularIp2",
        "cellularUptime2",
    ];

    const rows = getRows(undefined, [
        ...(isAccessGranted({ admin: true }) ? [] : adminRowNames),
        ...(isAccessGranted({ adminVpn: true }) ? [] : adminVpnRowNames),
        ...(isAccessGranted({ adminVpn: true, vpn: true }) ? [] : vpnRowNames),
        ...(isAccessGranted({ admin: true, smartems: true }) ? [] : smartemsRowNames),
    ]);

    const getTitleProps = composeGetTitleProps(device);

    return (
        <>
            <Display
                {...{
                    result: device,
                    rows,
                    getTitleProps,
                }}
            />
            <Box {...{ sx: { display: "flex", justifyContent: "space-between", gap: 2, mt: 2 } }}>
                <Box
                    {...{
                        sx: {
                            display: "flex",
                            flexWrap: "wrap",
                            alignItems: "center",
                            gap: 1,
                        },
                    }}
                >
                    <ResultDelete
                        {...{
                            denyBehavior: "hide",
                            result: device,
                            dialogProps: (result) => ({
                                confirmProps: {
                                    endpoint: "/device/" + result.id,
                                    onSuccess: (defaultOnSuccess) => {
                                        defaultOnSuccess();
                                        navigate("/device/list");
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
                    <CertificatesExpand {...{ result: device, entityPrefix: "device" }} />
                    <VpnExpand {...{ result: device }} />
                    {isAccessGranted({ admin: true, smartems: true }) && <ShowConfigExpand {...{ device }} />}
                    <ResultEdit
                        {...{
                            result: device,
                            to: (result) => "/device/edit/" + result.id,
                        }}
                    />
                </Box>
            </Box>
        </>
    );
};

export default DeviceDetailsDisplay;
export { DeviceDetailsDisplayProps };
