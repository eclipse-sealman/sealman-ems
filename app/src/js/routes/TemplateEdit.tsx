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
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import getFields from "~app/entities/Template/fields";
import { TemplateInterface } from "~app/entities/Template/definitions";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";

const TemplateEdit = () => {
    const navigate = useNavigate();
    const { id } = useParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [template, setTemplate] = React.useState<undefined | TemplateInterface>(undefined);

    React.useEffect(() => load(), []);

    const load = () => {
        showLoader();

        axios
            .get("/template/" + id)
            .then((response) => {
                setTemplate(response.data);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });
    };

    if (typeof template === "undefined") {
        return null;
    }

    const fields = getFields();

    return (
        <>
            <SurfaceTitle
                {...{
                    title: "route.title.template",
                    titleTo: "/template/list",
                    subtitle: "route.subtitle.detailsRepresentationDeviceType",
                    subtitleVariables: {
                        representation: template.representation,
                        deviceType: template.deviceType?.name,
                    },
                    subtitleTo: "/template/details/" + id,
                    hint: "route.hint.edit",
                    icon: <ContentCopyOutlined />,
                }}
            />
            <Surface>
                <Form
                    {...{
                        initialValues: template,
                        endpoint: "/template/" + id,
                        children: (
                            <CrudFieldset
                                {...{
                                    fields,
                                    backButtonProps: { onClick: () => navigate("/template/list") },
                                }}
                            />
                        ),
                        onSubmitSuccess: (defaultOnSubmitSuccess) => {
                            defaultOnSubmitSuccess();
                            navigate("/template/list");
                        },
                        fields,
                    }}
                />
            </Surface>
        </>
    );
};

export default TemplateEdit;
