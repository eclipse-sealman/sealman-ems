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
import { VpnLockOutlined } from "@mui/icons-material";
import { DeviceInterface } from "~app/entities/Device/definitions";
import ResultVpnDownloadConfig from "~app/components/Table/actions/ResultVpnDownloadConfig";
import ResultVpnOpenConnection from "~app/components/Table/actions/ResultVpnOpenConnection";
import ResultVpnOpenAllConnections from "~app/components/Table/actions/ResultVpnOpenAllConnections";
import ResultButtonExpand from "~app/components/Common/ResultButtonExpand";
import VpnCloseOwnedConnection from "~app/entities/Device/actions/VpnCloseOwnedConnection";
import ResultVpnCloseMultipleConnections from "~app/components/Table/actions/ResultVpnCloseMultipleConnections";

interface VpnExpandProps {
    result?: DeviceInterface;
}

const VpnExpand = ({ result }: VpnExpandProps) => {
    if (typeof result === "undefined") {
        throw new Error("VpnExpand component: Missing required result prop");
    }

    return (
        <ResultButtonExpand
            {...{
                result,
                label: "action.vpn",
                startIcon: <VpnLockOutlined />,
                denyBehavior: "hide",
                denyKey: "vpnActions",
                deny: result?.deny,
            }}
        >
            <ResultVpnOpenConnection entityPrefix="device" />
            <ResultVpnOpenAllConnections />
            <VpnCloseOwnedConnection />
            <ResultVpnCloseMultipleConnections />
            <ResultVpnDownloadConfig entityPrefix="device" />
        </ResultButtonExpand>
    );
};

export default VpnExpand;
export { VpnExpandProps };
