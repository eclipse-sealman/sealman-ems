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
import { ContentCopyOutlined } from "@mui/icons-material";
import { Form, useHandleCatch, useLoader } from "@arteneo/forge";
import { useNavigate, useParams } from "react-router-dom";
import { DeviceTypeInterface } from "~app/entities/DeviceType/definitions";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import getFields from "~app/entities/Template/fields";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";

const TemplateCreate = () => {
    const navigate = useNavigate();
    const { deviceTypeId } = useParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

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

    if (typeof deviceType !== "undefined") {
        const fields = getFields();

        content = (
            <Form
                {...{
                    endpoint: "/template/create",
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

                        return values;
                    },
                    onSubmitSuccess: (defaultOnSubmitSuccess) => {
                        defaultOnSubmitSuccess();
                        navigate("/template/list");
                    },
                    fields,
                }}
            />
        );
    }

    const titleProps: SurfaceTitleProps = {
        title: "route.title.template",
        titleTo: "/template/list",
        subtitle: "route.subtitle.create",
        icon: <ContentCopyOutlined />,
    };

    if (deviceType?.name) {
        titleProps.hint = "route.hint.selectedDeviceType";
        titleProps.hintVariables = { deviceType: deviceType?.name };
    }

    return (
        <>
            <SurfaceTitle {...titleProps} />
            <Surface>{content}</Surface>
        </>
    );
};

export default TemplateCreate;
