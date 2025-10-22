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
import { AXIOS_CANCELLED_UNMOUNTED, EndpointType, resolveEndpoint, useHandleCatch, useLoader } from "@arteneo/forge";
import { useDeepCompareEffectNoCheck } from "use-deep-compare-effect";
import SelectTiles, { SelectTilesProps } from "~app/components/Page/SelectTiles";

interface SelectEndpointTilesProps<T> extends Omit<SelectTilesProps<T>, "tiles"> {
    endpoint: EndpointType;
    processResponse?: (response: AxiosResponse) => T[];
}

const SelectEndpointTiles = <T extends object>({
    endpoint,
    processResponse = (response) => response.data,
    ...selectTilesProps
}: SelectEndpointTilesProps<T>) => {
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [tiles, setTiles] = React.useState<undefined | T[]>(undefined);

    const requestConfig = resolveEndpoint(endpoint);

    useDeepCompareEffectNoCheck(() => load(), [requestConfig]);

    React.useEffect(() => load(), []);

    const load = () => {
        showLoader();

        const axiosSource = axios.CancelToken.source();
        // requestConfig needs to be copied to avoid firing useDeepCompareEffectNoCheck
        const axiosRequestConfig = Object.assign({ cancelToken: axiosSource.token }, requestConfig);

        axios
            .request(axiosRequestConfig)
            .then((response) => {
                setTiles(processResponse(response));
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });

        return () => {
            axiosSource.cancel(AXIOS_CANCELLED_UNMOUNTED);
        };
    };

    return (
        <SelectTiles<T>
            {...{
                tiles,
                ...selectTilesProps,
            }}
        />
    );
};

export default SelectEndpointTiles;
export { SelectEndpointTilesProps };
