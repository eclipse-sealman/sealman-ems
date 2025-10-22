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
import { AXIOS_CANCELLED_UNMOUNTED, Form, FormProps, Optional, useHandleCatch } from "@arteneo/forge";
import { useNavigate, useParams } from "react-router-dom";
import axios, { AxiosError, AxiosResponse } from "axios";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";

interface ChangePasswordProps extends Optional<FormProps, "endpoint" | "children"> {
    endpointPrefix: string;
    titleProps: SurfaceTitleProps;
}

const ChangePassword = ({ endpointPrefix, titleProps, ...formProps }: ChangePasswordProps) => {
    const { id } = useParams();
    const navigate = useNavigate();

    const handleCatch = useHandleCatch();

    const [representation, setRepresentation] = React.useState<string>("...");
    const initializeEndpoint = endpointPrefix + "/" + id;

    React.useEffect(() => initializeValues(), [initializeEndpoint]);

    const initializeValues = () => {
        const axiosSource = axios.CancelToken.source();

        axios
            .get(initializeEndpoint, { cancelToken: axiosSource.token })
            .then((response: AxiosResponse) => {
                const object = response.data;
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
                    hint: "route.subtitle.changePassword",
                    ...titleProps,
                }}
            />
            <Surface>
                <Form
                    {...{
                        endpoint: endpointPrefix + "/changepassword/" + id,
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
            </Surface>
        </>
    );
};

export default ChangePassword;
export { ChangePasswordProps };
