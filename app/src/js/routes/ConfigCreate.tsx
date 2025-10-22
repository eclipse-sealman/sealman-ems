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
import axios from "axios";
import { SettingsOutlined } from "@mui/icons-material";
import { Form, useHandleCatch, useLoader } from "@arteneo/forge";
import { useNavigate, useParams } from "react-router-dom";
import { DeviceTypeInterface } from "~app/entities/DeviceType/definitions";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import composeGetFields from "~app/entities/Config/fields";
import {
    getDefaultConfigGenerator,
    getFeatureName,
    getFormatConfig,
    getHasAlwaysReinstallConfig,
} from "~app/entities/Config/utilities";
import { FeatureType } from "~app/enums/Feature";
import { useConfiguration } from "~app/contexts/Configuration";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";

const ConfigCreate = () => {
    const navigate = useNavigate();
    const { deviceTypeId, feature } = useParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();
    const { configGeneratorPhp, configGeneratorTwig } = useConfiguration();

    const [deviceType, setDeviceType] = React.useState<undefined | DeviceTypeInterface>(undefined);

    React.useEffect(() => load(), []);

    const load = () => {
        showLoader();

        axios
            .get("/options/devicetype/" + deviceTypeId)
            .then((response) => {
                setDeviceType(response.data);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });
    };

    let content = null;
    let featureName = "";

    if (typeof deviceType !== "undefined") {
        featureName = getFeatureName(deviceType, feature as FeatureType);
        const formatConfig = getFormatConfig(deviceType, feature as FeatureType);
        const hasAlwaysReinstallConfig = getHasAlwaysReinstallConfig(deviceType, feature as FeatureType);

        const getFields = composeGetFields(
            formatConfig,
            hasAlwaysReinstallConfig,
            configGeneratorPhp,
            configGeneratorTwig
        );
        const fields = getFields();

        content = (
            <Form
                {...{
                    initialValues: {
                        generator: getDefaultConfigGenerator(configGeneratorTwig),
                    },
                    endpoint: "/config/create",
                    children: (
                        <CrudFieldset
                            {...{
                                fields,
                                backButtonProps: { onClick: () => navigate(-1) },
                            }}
                        />
                    ),
                    changeSubmitValues: (values) => {
                        values.deviceType = deviceTypeId;
                        values.feature = feature;

                        return values;
                    },
                    onSubmitSuccess: (defaultOnSubmitSuccess) => {
                        defaultOnSubmitSuccess();
                        navigate("/config/list");
                    },
                    fields,
                }}
            />
        );
    }

    const titleProps: SurfaceTitleProps = {
        title: "route.title.config",
        titleTo: "/config/list",
        subtitle: "route.subtitle.create",
        icon: <SettingsOutlined />,
    };

    if (deviceType?.name && featureName) {
        titleProps.hint = "route.hint.selectedDeviceTypeAndFeature";
        titleProps.hintVariables = { deviceType: deviceType?.name, feature: featureName };
    }

    return (
        <>
            <SurfaceTitle {...titleProps} />
            <Surface>{content}</Surface>
        </>
    );
};

export default ConfigCreate;
