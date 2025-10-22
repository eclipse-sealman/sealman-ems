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
import { EditOutlined } from "@mui/icons-material";
import axios from "axios";
import {
    ButtonDialogFormFieldset,
    ColumnActionInterface,
    ButtonDialogFormFieldsetProps,
    Optional,
    useHandleCatch,
    AXIOS_CANCELLED_UNMOUNTED,
} from "@arteneo/forge";
import { ConfigInterface } from "~app/entities/Config/definitions";
import { getFormatConfig, getHasAlwaysReinstallConfig } from "~app/entities/Config/utilities";
import composeGetFields from "~app/entities/Config/fields";
import { useConfiguration } from "~app/contexts/Configuration";
import { useDetails } from "~app/contexts/Details";

type ConfigEditProps = ColumnActionInterface & Optional<ButtonDialogFormFieldsetProps, "dialogProps">;

const ConfigEdit = ({ result, ...props }: ConfigEditProps) => {
    const { configGeneratorPhp, configGeneratorTwig } = useConfiguration();
    const { reload } = useDetails();
    const handleCatch = useHandleCatch();
    const [config, setConfig] = React.useState<undefined | ConfigInterface>(undefined);

    React.useEffect(() => load(), []);

    if (typeof result === "undefined") {
        throw new Error("ConfigEdit component: Missing required result prop");
    }

    // We need to load config to get deny information
    const load = () => {
        const axiosSource = axios.CancelToken.source();

        axios
            .get("/config/" + result.id, { cancelToken: axiosSource.token })
            .then((response) => setConfig(response.data))
            .catch((error) => handleCatch(error));

        return () => {
            axiosSource.cancel(AXIOS_CANCELLED_UNMOUNTED);
        };
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
        config.id
    );
    const fields = getFields();

    return (
        <ButtonDialogFormFieldset
            {...{
                label: "action.edit",
                color: "info",
                variant: "contained",
                startIcon: <EditOutlined />,
                deny: config?.deny,
                denyKey: "edit",
                ...props,
                dialogProps: {
                    title: "configEdit.dialog.title",
                    dialogProps: {
                        maxWidth: "lg",
                    },
                    ...props.dialogProps,
                    formProps: {
                        fields,
                        initialValues: config,
                        endpoint: "/config/" + config.id,
                        onSubmitSuccess: (defaultOnSubmitSuccess) => {
                            defaultOnSubmitSuccess();
                            reload();
                        },
                    },
                },
            }}
        />
    );
};

export default ConfigEdit;
export { ConfigEditProps };
