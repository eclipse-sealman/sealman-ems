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
import { AXIOS_CANCELLED_UNMOUNTED, useHandleCatch } from "@arteneo/forge";
import { useNavigate, useParams } from "react-router-dom";
import axios, { AxiosError, AxiosResponse } from "axios";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import CrudCreate, { CreateProps as CrudCreateProps } from "~app/components/Crud/Create";

const Create = ({ endpointPrefix, titleProps, ...formProps }: CrudCreateProps) => {
    const { deviceId, deviceTypeSecretId } = useParams();
    const navigate = useNavigate();
    const handleCatch = useHandleCatch();

    const [representation, setRepresentation] = React.useState<string>("...");
    const initializeEndpoint = endpointPrefix + "/" + deviceId + "/" + deviceTypeSecretId + "/info";

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
        <CrudCreate
            {...{
                endpointPrefix: endpointPrefix,
                titleProps: {
                    subtitle: representation,
                    disableSubtitleTranslate: true,
                    hint: "route.hint.createSecret",
                    ...titleProps,
                },
                endpoint: endpointPrefix + "/" + deviceId + "/" + deviceTypeSecretId + "/create",
                children: (
                    <CrudFieldset
                        {...{
                            fields: formProps.fields,
                        }}
                    />
                ),
                onSubmitSuccess: (defaultOnSubmitSuccess: () => void) => {
                    defaultOnSubmitSuccess();
                    navigate(-1);
                },
                ...formProps,
            }}
        />
    );
};

export default Create;
export { CrudCreateProps as CreateProps };
