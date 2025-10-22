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
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import composeGetFields from "~app/entities/Config/fields";
import { ConfigInterface } from "~app/entities/Config/definitions";
import { getFeatureName, getFormatConfig, getHasAlwaysReinstallConfig } from "~app/entities/Config/utilities";
import { useConfiguration } from "~app/contexts/Configuration";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";

const ConfigEdit = () => {
    const navigate = useNavigate();
    const { id } = useParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();
    const { configGeneratorPhp, configGeneratorTwig } = useConfiguration();

    const [config, setConfig] = React.useState<undefined | ConfigInterface>(undefined);

    React.useEffect(() => load(), []);

    const load = () => {
        showLoader();

        axios
            .get("/config/" + id)
            .then((response) => {
                setConfig(response.data);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });
    };

    if (typeof config === "undefined") {
        return null;
    }

    const formatConfig = getFormatConfig(config.deviceType, config.feature);
    const hasAlwaysReinstallConfig = getHasAlwaysReinstallConfig(config.deviceType, config.feature);

    const getFields = composeGetFields(
        formatConfig,
        hasAlwaysReinstallConfig,
        configGeneratorPhp,
        configGeneratorTwig,
        config.feature,
        id
    );
    const fields = getFields();

    return (
        <>
            <SurfaceTitle
                {...{
                    title: "route.title.config",
                    titleTo: "/config/list",
                    subtitle: "route.subtitle.detailsRepresentationDeviceTypeAndFeature",
                    subtitleVariables: {
                        representation: config.representation,
                        deviceType: config.deviceType?.name,
                        feature: getFeatureName(config.deviceType, config.feature),
                    },
                    hint: "route.hint.edit",
                    icon: <SettingsOutlined />,
                }}
            />
            <Surface>
                <Form
                    {...{
                        initialValues: config,
                        endpoint: "/config/" + config.id,
                        children: (
                            <CrudFieldset
                                {...{
                                    fields,
                                    backButtonProps: { onClick: () => navigate("/config/list") },
                                }}
                            />
                        ),
                        onSubmitSuccess: (defaultOnSubmitSuccess) => {
                            defaultOnSubmitSuccess();
                            navigate("/config/list");
                        },
                        fields,
                    }}
                />
            </Surface>
        </>
    );
};

export default ConfigEdit;
