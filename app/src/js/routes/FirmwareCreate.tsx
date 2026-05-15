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
import { MemoryOutlined } from "@mui/icons-material";
import { Form } from "@arteneo/forge";
import { useNavigate, useParams } from "react-router-dom";
import { DeviceTypeInterface } from "~app/entities/DeviceType/definitions";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import composeGetFields from "~app/entities/Firmware/createFields";
import { getFeatureName } from "~app/entities/Firmware/utilities";
import { FeatureType } from "~app/enums/Feature";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";
import { FormikValues } from "formik";
import { cloneDeep } from "lodash";
import useEndpoint from "~app/hooks/useEndpoint";

const FirmwareCreate = () => {
    const navigate = useNavigate();
    const { deviceTypeId, feature } = useParams();

    const { object: deviceType } = useEndpoint<DeviceTypeInterface>("/options/devicetype/" + deviceTypeId);

    let content = null;
    let featureName = "";

    if (typeof deviceType !== "undefined") {
        featureName = getFeatureName(deviceType, feature as FeatureType);

        const getFields = composeGetFields(deviceType, feature as FeatureType);
        const fields = getFields();

        content = (
            <Form
                {...{
                    initialValues: {
                        sourceType: "upload",
                    },
                    endpoint: "/firmware/create",
                    children: <CrudFieldset {...{ fields }} />,
                    changeSubmitValues: (values) => {
                        const changedValues: FormikValues = {
                            deviceType: deviceTypeId,
                            feature,
                            ...cloneDeep(values),
                        };

                        if (changedValues?.sourceType !== "upload") {
                            delete changedValues.filepath;
                        }

                        if (changedValues?.sourceType !== "externalUrl") {
                            delete changedValues.externalUrl;
                            delete changedValues.md5;
                        }

                        return changedValues;
                    },
                    onSubmitSuccess: (defaultOnSubmitSuccess) => {
                        defaultOnSubmitSuccess();
                        navigate("/firmware/list");
                    },
                    fields,
                }}
            />
        );
    }

    const titleProps: SurfaceTitleProps = {
        title: "route.title.firmware",
        titleTo: "/firmware/list",
        subtitle: "route.subtitle.create",
        icon: <MemoryOutlined />,
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

export default FirmwareCreate;
