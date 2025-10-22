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
import { AXIOS_CANCELLED_UNMOUNTED, Form, FormProps, Optional, resolveEndpoint, useHandleCatch } from "@arteneo/forge";
import { useNavigate, useParams } from "react-router-dom";
import { FormikValues } from "formik";
import axios, { AxiosError, AxiosResponse } from "axios";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";
import { useDeepCompareEffectNoCheck } from "use-deep-compare-effect";

interface EditProps extends Optional<FormProps, "endpoint" | "children"> {
    endpointPrefix: string;
    titleProps: SurfaceTitleProps;
}

const Edit = ({ endpointPrefix, titleProps, initializeEndpoint, ...formProps }: EditProps) => {
    const { id } = useParams();
    const navigate = useNavigate();
    const handleCatch = useHandleCatch();

    const [initialValues, setInitialValues] = React.useState<undefined | FormikValues>(undefined);
    const [representation, setRepresentation] = React.useState<string>("...");

    const initializeEndpointResolved = resolveEndpoint(initializeEndpoint ?? endpointPrefix + "/" + id);

    useDeepCompareEffectNoCheck(() => initializeValues(), [initializeEndpointResolved]);

    React.useEffect(() => initializeValues(), []);

    const initializeValues = () => {
        const axiosSource = axios.CancelToken.source();

        const axiosRequestConfig = Object.assign({ cancelToken: axiosSource.token }, initializeEndpointResolved);

        axios
            .request(axiosRequestConfig)
            .then((response: AxiosResponse) => {
                const object = response.data;
                setInitialValues(object);
                setRepresentation(object?.representation ?? "...");
            })
            .catch((error: AxiosError) => {
                handleCatch(error);
            });

        return () => {
            axiosSource.cancel(AXIOS_CANCELLED_UNMOUNTED);
        };
    };

    return (
        <>
            <SurfaceTitle
                {...{
                    subtitle: representation,
                    disableSubtitleTranslate: true,
                    hint: "route.hint.edit",
                    ...titleProps,
                }}
            />
            <Surface>
                {typeof initialValues !== "undefined" && (
                    <Form
                        {...{
                            initialValues,
                            endpoint: endpointPrefix + "/" + id,
                            children: (
                                <CrudFieldset
                                    {...{
                                        fields: formProps.fields,
                                        backButtonProps: { onClick: () => navigate("../list") },
                                    }}
                                />
                            ),
                            onSubmitSuccess: (defaultOnSubmitSuccess) => {
                                defaultOnSubmitSuccess();
                                navigate("../list");
                            },
                            ...formProps,
                        }}
                    />
                )}
            </Surface>
        </>
    );
};

export default Edit;
export { EditProps };
