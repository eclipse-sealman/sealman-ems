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
import { MenuBookOutlined } from "@mui/icons-material";
import composeGetFields from "~app/entities/Configuration/documentationFields";
import ConfigurationChange, { ConfigurationChangeInterface } from "~app/routes/Configuration/ConfigurationChange";
import { useConfiguration } from "~app/contexts/Configuration";

const documentationConfiguration: ConfigurationChangeInterface = {
    endpoint: "/documentation",
    title: "documentation",
    to: "/documentation",
    icon: <MenuBookOutlined />,
};

const Documentation = () => {
    const { isVpnAvailable, reload } = useConfiguration();

    const getFields = composeGetFields(isVpnAvailable);
    const fields = getFields();

    return (
        <ConfigurationChange
            {...{
                configuration: documentationConfiguration,
                fields,
                onSubmitSuccess(defaultOnSubmitSuccess) {
                    defaultOnSubmitSuccess();
                    // REST API documentation may be enabled or disabled so we reload configuration
                    reload();
                },
            }}
        />
    );
};

export default Documentation;
export { documentationConfiguration };
