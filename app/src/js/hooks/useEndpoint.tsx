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
import { AXIOS_CANCELLED_UNMOUNTED, EndpointType, resolveEndpoint, useHandleCatch, useLoader } from "@arteneo/forge";
import axios from "axios";
import { useDeepCompareEffectNoCheck } from "use-deep-compare-effect";

interface UseEndpointInterface<T> {
    object?: T;
    loading: boolean;
}

const useEndpoint = <T,>(endpoint: EndpointType): UseEndpointInterface<T> => {
    const requestConfig = resolveEndpoint(endpoint);
    if (!requestConfig) {
        throw new Error("Could not resolve endpoint");
    }

    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [object, setObject] = React.useState<undefined | T>(undefined);
    const [loading, setLoading] = React.useState(true);

    const load = () => {
        setLoading(true);
        showLoader();

        const axiosSource = axios.CancelToken.source();
        // requestConfig needs to be copied to avoid firing useDeepCompareEffectNoCheck
        const axiosRequestConfig = Object.assign({ cancelToken: axiosSource.token }, requestConfig);

        axios
            .request(axiosRequestConfig)
            .then((response) => {
                setObject(response.data);
                setLoading(false);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                setLoading(false);
                handleCatch(error);
            });

        return () => {
            axiosSource.cancel(AXIOS_CANCELLED_UNMOUNTED);
        };
    };

    useDeepCompareEffectNoCheck(() => load(), [requestConfig]);

    return { object, loading };
};

export default useEndpoint;
export { UseEndpointInterface };
