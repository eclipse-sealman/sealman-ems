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
import { BuildOutlined } from "@mui/icons-material";
import getFields from "~app/entities/Configuration/generalFields";
import ConfigurationChange, { ConfigurationChangeInterface } from "~app/routes/Configuration/ConfigurationChange";
import { useConfiguration } from "~app/contexts/Configuration";

const generalConfiguration: ConfigurationChangeInterface = {
    endpoint: "/general",
    title: "general",
    to: "/general",
    icon: <BuildOutlined />,
};

const General = () => {
    const { reload } = useConfiguration();
    const fields = getFields();

    return (
        <ConfigurationChange
            {...{
                configuration: generalConfiguration,
                fields,
                changeSubmitValues: (values) => {
                    if (!values?.failedLoginAttemptsEnabled) {
                        delete values.failedLoginAttemptsLimit;
                        delete values.failedLoginAttemptsDisablingDuration;
                    }

                    return values;
                },
                onSubmitSuccess(defaultOnSubmitSuccess) {
                    defaultOnSubmitSuccess();
                    // Generator PHP or Twig configuration may be enabled or disabled so we reload configuration
                    reload();
                },
            }}
        />
    );
};

export default General;
export { generalConfiguration };
