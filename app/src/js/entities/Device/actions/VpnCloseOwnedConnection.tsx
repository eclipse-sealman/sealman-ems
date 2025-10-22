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
import { ResultInterface } from "@arteneo/forge";
import ResultVpnCloseConnection, {
    ResultVpnCloseConnectionProps,
} from "~app/components/Table/actions/ResultVpnCloseConnection";

const VpnCloseOwnedConnection = ({ result, ...props }: ResultVpnCloseConnectionProps) => {
    if (typeof result === "undefined") {
        throw new Error("VpnCloseOwnedConnection component: Missing required result prop");
    }

    const vpnConnection = result?.ownedVpnConnections?.find(
        (vpnConnection: ResultInterface) => vpnConnection.target?.id === result.id
    );
    if (!vpnConnection) {
        return null;
    }

    return (
        <ResultVpnCloseConnection
            {...{
                label: "action.vpnCloseOwnedConnection",
                result: vpnConnection,
                deny: result.deny,
                ...props,
            }}
        />
    );
};

export default VpnCloseOwnedConnection;
export { ResultVpnCloseConnectionProps as VpnCloseOwnedConnectionProps };
