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
import getFields from "~app/entities/Configuration/vpnFields";
import ConfigurationChange, { ConfigurationChangeInterface } from "~app/routes/Configuration/ConfigurationChange";

const vpnConfiguration: ConfigurationChangeInterface = {
    endpoint: "/vpn",
    title: "vpn",
    to: "/vpn",
    icon: <VpnLockOutlined />,
};

const Vpn = () => {
    const fields = getFields();

    return <ConfigurationChange {...{ configuration: vpnConfiguration, fields }} />;
};

export default Vpn;
export { vpnConfiguration };
