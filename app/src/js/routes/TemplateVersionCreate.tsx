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
import { FormikValues } from "formik";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import composeGetFields from "~app/entities/TemplateVersion/fields";
import { TemplateInterface } from "~app/entities/Template/definitions";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";
import { useUser } from "~app/contexts/User";

const TemplateVersionCreate = () => {
    const { isAccessGranted } = useUser();
    const navigate = useNavigate();
    const { templateId } = useParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [template, setTemplate] = React.useState<undefined | TemplateInterface>(undefined);

    React.useEffect(() => load(), []);

    const load = () => {
        showLoader();

        axios
            .get("/template/" + templateId)
            .then((response) => {
                setTemplate(response.data);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });
    };

    let content = null;

    if (typeof template !== "undefined") {
        const getFields = composeGetFields(
            template.deviceType,
            !isAccessGranted({ admin: true }),
            !isAccessGranted({ adminVpn: true })
        );
        const fields = getFields();

        const initialValues: FormikValues = {};
        if (typeof fields.masqueradeType !== "undefined") {
            initialValues.masqueradeType = template.deviceType?.masqueradeType;
        }

        if (typeof fields.virtualSubnetCidr !== "undefined") {
            initialValues.virtualSubnetCidr = template.deviceType?.virtualSubnetCidr;
        }

        content = (
            <Form
                {...{
                    endpoint: "/templateversion/create/staging/" + templateId,
                    initialValues,
                    children: (
                        <CrudFieldset
                            {...{
                                fields,
                                backButtonProps: { onClick: () => navigate(-1) },
                            }}
                        />
                    ),
                    onSubmitSuccess: (defaultOnSubmitSuccess) => {
                        defaultOnSubmitSuccess();
                        navigate("/template/details/" + templateId);
                    },
                    fields,
                }}
            />
        );
    }

    const titleProps: SurfaceTitleProps = {
        title: "route.title.template",
        titleTo: "/template/list",
        subtitle: "...",
        subtitleTo: "/template/details/" + templateId,
        disableSubtitleTranslate: true,
        hint: "route.hint.createTemplateVersion",
        icon: <ContentCopyOutlined />,
    };

    if (typeof template !== "undefined") {
        titleProps.subtitle = "route.subtitle.detailsRepresentationDeviceType";
        titleProps.subtitleVariables = {
            representation: template.representation,
            deviceType: template.deviceType?.name,
        };
        titleProps.disableSubtitleTranslate = false;
    }

    return (
        <>
            <SurfaceTitle {...titleProps} />
            <Surface>{content}</Surface>
        </>
    );
};

export default TemplateVersionCreate;
