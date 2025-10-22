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
import { AppBlockingOutlined } from "@mui/icons-material";
import composeGetFields from "~app/entities/Configuration/totpFields";
import ConfigurationChange, { ConfigurationChangeInterface } from "~app/routes/Configuration/ConfigurationChange";
import { useConfiguration } from "~app/contexts/Configuration";
import ConfigurationTotpFieldset from "~app/fieldsets/ConfigurationTotpFieldset";

const totpConfiguration: ConfigurationChangeInterface = {
    endpoint: "/totp",
    title: "totp",
    to: "/totp",
    icon: <AppBlockingOutlined />,
};

const Totp = () => {
    const { isTotpSecretGenerated, reload } = useConfiguration();

    const getFields = composeGetFields(isTotpSecretGenerated);
    const fields = getFields();

    return (
        <ConfigurationChange
            {...{
                configuration: totpConfiguration,
                fields,
                changeSubmitValues: (values) => {
                    if (!values?.totpEnabled) {
                        delete values.totpKeyRegeneration;
                        delete values.totpWindow;
                        delete values.totpTokenLength;
                        delete values.totpSecretLength;
                        delete values.totpAlgorithm;
                    }

                    return values;
                },
                children: <ConfigurationTotpFieldset {...{ fields }} />,
                onSubmitSuccess(defaultOnSubmitSuccess) {
                    defaultOnSubmitSuccess();
                    // TOTP configuration may be enabled or disabled so we reload configuration
                    reload();
                },
            }}
        />
    );
};

export default Totp;
export { totpConfiguration };
