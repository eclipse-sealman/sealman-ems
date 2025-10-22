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
import { SyncLockOutlined } from "@mui/icons-material";
import getFields from "~app/entities/Configuration/ssoFields";
import ConfigurationChange, { ConfigurationChangeInterface } from "~app/routes/Configuration/ConfigurationChange";
import { useConfiguration } from "~app/contexts/Configuration";
import ConfigurationSsoFieldset from "~app/fieldsets/ConfigurationSsoFieldset";
import { useUser } from "~app/contexts/User";

const ssoConfiguration: ConfigurationChangeInterface = {
    endpoint: "/sso",
    title: "sso",
    to: "/sso",
    icon: <SyncLockOutlined />,
};

const Sso = () => {
    const { isAccessGranted } = useUser();
    const { reload } = useConfiguration();

    const fields = getFields(
        undefined,
        isAccessGranted({ adminVpn: true }) ? undefined : ["ssoRoleVpnCertificateAutoGenerate"]
    );

    return (
        <ConfigurationChange
            {...{
                configuration: ssoConfiguration,
                fields,
                children: <ConfigurationSsoFieldset {...{ fields }} />,
                onSubmitSuccess(defaultOnSubmitSuccess) {
                    defaultOnSubmitSuccess();
                    // SSO configuration may be changed so we reload configuration
                    reload();
                },
            }}
        />
    );
};

export default Sso;
export { ssoConfiguration };
