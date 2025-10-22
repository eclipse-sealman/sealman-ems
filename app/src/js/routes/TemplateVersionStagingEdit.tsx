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
import { FormikValues } from "formik";
import { Form, useHandleCatch, useLoader } from "@arteneo/forge";
import { useNavigate, useParams } from "react-router-dom";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import composeGetFields from "~app/entities/TemplateVersion/fields";
import { TemplateVersionInterface } from "~app/entities/TemplateVersion/definitions";
import { DeviceConfigurationTypeInterface } from "~app/entities/DeviceType/definitions";
import { useUser } from "~app/contexts/User";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";

const TemplateVersionStagingEdit = () => {
    const { isAccessGranted } = useUser();
    const navigate = useNavigate();
    const { id } = useParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [templateVersion, setTemplateVersion] = React.useState<undefined | TemplateVersionInterface>(undefined);

    React.useEffect(() => load(), []);

    const load = () => {
        showLoader();

        axios
            .get("/templateversion/" + id)
            .then((response) => {
                setTemplateVersion(response.data);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });
    };

    if (typeof templateVersion === "undefined") {
        return null;
    }

    const deviceType = templateVersion.deviceType;
    const getFields = composeGetFields(
        deviceType,
        !isAccessGranted({ admin: true }),
        !isAccessGranted({ adminVpn: true }),
        templateVersion.template.id
    );
    const fields = getFields();

    const initialValues = Object.assign(templateVersion, getReinstallInitialValues(deviceType));
    const template = templateVersion?.template;

    return (
        <>
            <SurfaceTitle
                {...{
                    title: "route.title.template",
                    titleTo: "/template/list",
                    subtitle: "route.subtitle.detailsRepresentationDeviceType",
                    subtitleVariables: {
                        representation: template.representation,
                        deviceType: templateVersion.deviceType?.name,
                    },
                    subtitleTo: "/template/details/" + template?.id,
                    hint: "route.hint.editRepresentation",
                    hintVariables: {
                        representation: templateVersion?.representation,
                    },
                    icon: <ContentCopyOutlined />,
                }}
            />
            <Surface>
                <Form
                    {...{
                        initialValues,
                        endpoint: "/templateversion/" + templateVersion.id,
                        children: (
                            <CrudFieldset
                                {...{
                                    fields,
                                    backButtonProps: {
                                        onClick: () => navigate("/template/details/" + templateVersion.template?.id),
                                    },
                                }}
                            />
                        ),
                        onSubmitSuccess: (defaultOnSubmitSuccess) => {
                            defaultOnSubmitSuccess();
                            navigate("/template/details/" + templateVersion.template?.id);
                        },
                        fields,
                    }}
                />
            </Surface>
        </>
    );
};

const getReinstallInitialValues = (deviceType: DeviceConfigurationTypeInterface): FormikValues => {
    const initialValues: FormikValues = {};
    if (deviceType.hasConfig1) {
        initialValues.reinstallConfig1 = false;
    }
    if (deviceType.hasConfig2) {
        initialValues.reinstallConfig2 = false;
    }
    if (deviceType.hasConfig3) {
        initialValues.reinstallConfig3 = false;
    }
    if (deviceType.hasFirmware1) {
        initialValues.reinstallFirmware1 = false;
    }
    if (deviceType.hasFirmware2) {
        initialValues.reinstallFirmware2 = false;
    }
    if (deviceType.hasFirmware3) {
        initialValues.reinstallFirmware3 = false;
    }

    return initialValues;
};

export default TemplateVersionStagingEdit;
export { getReinstallInitialValues };
