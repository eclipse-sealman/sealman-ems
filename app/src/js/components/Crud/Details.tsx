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
import { AXIOS_CANCELLED_UNMOUNTED, useHandleCatch, useLoader } from "@arteneo/forge";
import { useLocation, useParams } from "react-router-dom";
import axios from "axios";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";
import { useDetails } from "~app/contexts/Details";

interface DetailsProps {
    endpointPrefix: string;
    // eslint-disable-next-line
    render: (object: any) => React.ReactNode;
    titleProps?: Partial<SurfaceTitleProps>;
    // eslint-disable-next-line
    objectTitleProps?: (object: any) => Partial<SurfaceTitleProps>;
}

const Details = ({ endpointPrefix, render, titleProps, objectTitleProps }: DetailsProps) => {
    const { id } = useParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();
    const { reloadCounter } = useDetails();
    const location = useLocation();

    // eslint-disable-next-line
    const [object, setObject] = React.useState<undefined | any>(undefined);
    // We need internal loading flag to avoid re-rendering when child component shows loader
    const [loading, setLoading] = React.useState(false);

    React.useEffect(() => load(), [reloadCounter]);

    const load = () => {
        showLoader();
        setLoading(true);

        const axiosSource = axios.CancelToken.source();

        axios
            .get(endpointPrefix + "/" + id)
            .then((response) => {
                setObject(response.data);
                hideLoader();
                setLoading(false);
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

    const isObjectReady = !loading && typeof object !== "undefined";
    let internalTitleProps: SurfaceTitleProps = {
        subtitle: "...",
        disableSubtitleTranslate: true,
        hint: "route.hint.details",
    };

    if (typeof titleProps !== "undefined") {
        internalTitleProps = Object.assign(internalTitleProps, titleProps);
    }

    if (isObjectReady) {
        internalTitleProps.subtitle = object?.representation ?? "...";
        internalTitleProps.subtitleTo = location.pathname;

        if (typeof objectTitleProps !== "undefined") {
            internalTitleProps = Object.assign(internalTitleProps, objectTitleProps(object));
        }
    }

    return (
        <>
            <SurfaceTitle {...internalTitleProps} />
            {isObjectReady && render(object)}
        </>
    );
};

export default Details;
export { DetailsProps };
