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
import { Optional } from "@arteneo/forge";
import getFields from "~app/entities/Configuration/generalFields";
import Change, { ChangeProps } from "~app/components/Crud/Change";
import DashboardTileInterface from "~app/definitions/DashboardTileInterface";

interface ConfigurationChangeInterface extends DashboardTileInterface {
    endpoint: string;
}

interface ConfigurationChangeProps extends Optional<ChangeProps, "endpoint" | "fields" | "titleProps"> {
    configuration: ConfigurationChangeInterface;
}

const ConfigurationChange = ({ configuration, ...props }: ConfigurationChangeProps) => {
    const fields = getFields();

    return (
        <Change
            {...{
                endpoint: "/configuration" + configuration.endpoint,
                fields,
                ...props,
                titleProps: {
                    title: "route.title.configuration.dashboard",
                    titleTo: "/configuration/dashboard",
                    icon: configuration.icon,
                    subtitle: "route.title.configuration." + configuration.title,
                    ...props.titleProps,
                },
            }}
        />
    );
};

export default ConfigurationChange;
export { ConfigurationChangeProps, ConfigurationChangeInterface };
