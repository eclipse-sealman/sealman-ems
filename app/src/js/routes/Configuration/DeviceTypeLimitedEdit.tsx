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
import { FormikValues } from "formik";
import Surface from "~app/components/Common/Surface";
import composeGetFields from "~app/entities/DeviceType/limitedFields";
import { CommunicationProcedureRequirements } from "~app/entities/DeviceType/definitions";
import DeviceTypeLimitedFieldset from "~app/fieldsets/DeviceTypeLimitedFieldset";
import {
    hasNoneCommunicationProcedure,
    processDeviceTypeLimitedSubmitValues,
    processDeviceTypeCertificateTypesInitialValues,
} from "~app/entities/DeviceType/utilities";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";
import EntityInterface from "~app/definitions/EntityInterface";

const DeviceTypeLimitedEdit = () => {
    const navigate = useNavigate();
    const { id } = useParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [requirements, setRequirements] = React.useState<undefined | CommunicationProcedureRequirements>(undefined);
    const [usedCertificateTypes, setUsedCertificateTypes] = React.useState<EntityInterface[]>([]);
    const [initialValues, setInitialValues] = React.useState<undefined | FormikValues>(undefined);

    React.useEffect(() => load(), [id]);

    const load = () => {
        if (!id) {
            return;
        }
        showLoader();

        axios
            .get("/devicetype/" + id)
            .then((response) => {
                setInitialValues(response.data);

                axios
                    .get("/devicetype/communication/procedure/requirements/" + response.data?.communicationProcedure)
                    .then((response) => {
                        setRequirements(response.data);

                        axios
                            .get("/devicetype/used/certificate/types/" + id)
                            .then((response) => {
                                setUsedCertificateTypes(response.data);
                                hideLoader();
                            })
                            .catch((error) => {
                                hideLoader();
                                handleCatch(error);
                            });
                    })
                    .catch((error) => {
                        hideLoader();
                        handleCatch(error);
                    });
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });
    };

    let content = null;

    if (typeof requirements !== "undefined" && typeof initialValues !== "undefined") {
        const getFields = composeGetFields(requirements, usedCertificateTypes, initialValues);
        const fields = getFields();

        content = (
            <Form
                {...{
                    initialValues: initialValues,
                    endpoint: "/devicetype/limitededit/" + id,
                    children: (
                        <DeviceTypeLimitedFieldset
                            {...{
                                fields,
                                noCommunicationFields: initialValues.communicationProcedure === "none",
                                backButtonProps: { onClick: () => navigate(-1) },
                            }}
                        />
                    ),
                    processInitialValues: (
                        fields: FieldsInterface,
                        initialValues?: FormikValues,
                        response?: AxiosResponse
                    ) => processDeviceTypeCertificateTypesInitialValues(fields, requirements, initialValues, response),
                    changeSubmitValues: (values) =>
                        processDeviceTypeLimitedSubmitValues(
                            values,
                            initialValues.hasCertificates,
                            initialValues.hasEndpointDevices,
                            initialValues.hasMasquerade,
                            initialValues.hasDeviceCommands,
                            initialValues.hasConfig1 || initialValues.hasConfig2 || initialValues.hasConfig3,
                            initialValues.hasFirmware1 || initialValues.hasFirmware2 || initialValues.hasFirmware3,
                            hasNoneCommunicationProcedure(initialValues.communicationProcedure)
                        ),
                    onSubmitSuccess: (defaultOnSubmitSuccess) => {
                        defaultOnSubmitSuccess();
                        navigate("/configuration/devicetype/list");
                    },
                    fields,
                }}
            />
        );
    }

    const titleProps: SurfaceTitleProps = {
        title: "route.title.configuration.deviceType",
        titleTo: "/configuration/devicetype/list",
        subtitle: "...",
        subtitleTo: "/configuration/devicetype/details/" + id,
        disableSubtitleTranslate: true,
        hint: "route.hint.limitedEdit",
        icon: <RouterOutlined />,
    };

    if (typeof initialValues !== "undefined") {
        titleProps.subtitle = initialValues.representation;
    }

    return (
        <>
            <SurfaceTitle {...titleProps} />
            <Surface>{content}</Surface>
        </>
    );
};

export default DeviceTypeLimitedEdit;
export { CommunicationProcedureRequirements };
