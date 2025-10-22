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
import axios, { AxiosResponse } from "axios";
import { RouterOutlined } from "@mui/icons-material";
import { FieldsInterface, Form, useHandleCatch, useLoader } from "@arteneo/forge";
import { useNavigate, useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import Surface from "~app/components/Common/Surface";
import composeGetFields, { getIsEnableField } from "~app/entities/DeviceType/fields";
import DeviceTypeFieldset from "~app/fieldsets/DeviceTypeFieldset";
import {
    hasNoneCommunicationProcedure,
    processDeviceTypeSubmitValues,
    processDeviceTypeCertificateTypesInitialValues,
} from "~app/entities/DeviceType/utilities";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";
import { FormikValues } from "formik";
import { CommunicationProcedureRequirements } from "~app/entities/DeviceType/definitions";

const DeviceTypeCreate = () => {
    const navigate = useNavigate();
    const { t } = useTranslation();
    const { communicationProcedure } = useParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [requirements, setRequirements] = React.useState<undefined | CommunicationProcedureRequirements>(undefined);

    React.useEffect(() => load(), [communicationProcedure]);

    const load = () => {
        if (!communicationProcedure) {
            return;
        }
        showLoader();

        axios
            .get("/devicetype/communication/procedure/requirements/" + communicationProcedure)
            .then((response) => {
                setRequirements(response.data);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });
    };

    let content = null;

    if (typeof requirements !== "undefined") {
        const getFields = composeGetFields(requirements);
        const fields = getFields();

        content = (
            <Form
                {...{
                    initializeEndpoint: "/devicetype/default/" + communicationProcedure,
                    endpoint: "/devicetype/create",
                    children: (
                        <DeviceTypeFieldset
                            {...{
                                fields,
                                enableConfigMinRsrp: getIsEnableField(requirements, "hasConfig"),
                                enableFirmwareMinRsrp: getIsEnableField(requirements, "hasFirmware"),
                                noCommunicationFields: hasNoneCommunicationProcedure(communicationProcedure),
                                backButtonProps: { onClick: () => navigate(-1) },
                            }}
                        />
                    ),
                    processInitialValues: (
                        fields: FieldsInterface,
                        initialValues?: FormikValues,
                        response?: AxiosResponse
                    ) => processDeviceTypeCertificateTypesInitialValues(fields, requirements, initialValues, response),

                    changeSubmitValues: (values) => {
                        values.communicationProcedure = communicationProcedure;
                        return processDeviceTypeSubmitValues(
                            values,
                            communicationProcedure ? communicationProcedure : "none",
                            requirements
                        );
                    },
                    onSubmitSuccess: (defaultOnSubmitSuccess) => {
                        defaultOnSubmitSuccess();
                        navigate("/configuration/devicetype/list");
                    },
                    fields,
                }}
            />
        );
    }

    return (
        <>
            <SurfaceTitle
                {...{
                    title: "route.title.configuration.deviceType",
                    subtitle: "route.subtitle.create",
                    hint: "route.hint.selectedCommunicationProcedure",
                    hintVariables: {
                        communicationProcedure: t("enum.deviceType.communicationProcedure." + communicationProcedure),
                    },
                    icon: <RouterOutlined />,
                }}
            />
            <Surface>{content}</Surface>
        </>
    );
};

export default DeviceTypeCreate;
