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
import { RadarOutlined } from "@mui/icons-material";
import getFields from "~app/entities/Configuration/radiusFields";
import ConfigurationChange, { ConfigurationChangeInterface } from "~app/routes/Configuration/ConfigurationChange";
import { FormikValues } from "formik";
import ConfigurationRadiusFieldset from "~app/fieldsets/ConfigurationRadiusFieldset";

const radiusConfiguration: ConfigurationChangeInterface = {
    endpoint: "/radius",
    title: "radius",
    to: "/radius",
    icon: <RadarOutlined />,
};

const Radius = () => {
    const fields = getFields();

    const changeSubmitValues = (values: FormikValues): FormikValues => {
        // Values array is cloned because, if form will return validation errors, some fields might be cleared
        const _values = Object.assign({}, values);

        if (!_values.radiusEnabled) {
            delete _values.radiusAuth;
            delete _values.radiusServer;
            delete _values.radiusSecret;
            delete _values.radiusNasAddress;
            delete _values.radiusNasPort;
            delete _values.radiusWelotecGroupMappingEnabled;
            delete _values.radiusWelotecTagMappingEnabled;
            delete _values.radiusWelotecGroupMappings;
        }

        if (!_values.radiusWelotecGroupMappingEnabled) {
            delete _values.radiusWelotecTagMappingEnabled;
            delete _values.radiusWelotecGroupMappings;
        }

        return _values;
    };

    return (
        <ConfigurationChange
            {...{
                configuration: radiusConfiguration,
                fields,
                children: <ConfigurationRadiusFieldset {...{ fields }} />,
                changeSubmitValues: (values) => changeSubmitValues(values),
            }}
        />
    );
};

export default Radius;
export { radiusConfiguration };
