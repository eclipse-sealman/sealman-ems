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

import { AXIOS_CANCELLED_UNMOUNTED, useHandleCatch } from "@arteneo/forge";
import { Alert } from "@mui/material";
import axios, { AxiosError, AxiosRequestConfig, AxiosResponse } from "axios";
import React from "react";
import { useTranslation } from "react-i18next";
import { useDeepCompareEffectNoCheck } from "use-deep-compare-effect";
import DialogPre, { DialogPreProps } from "~app/components/Common/DialogPre";
import CircularLoader from "~app/components/Layout/CircularLoader";

interface DialogPreEndpointProps extends DialogPreProps {
    requestConfig: AxiosRequestConfig;
    processContent?: (response: AxiosResponse) => string;
}

const DialogPreEndpoint = ({
    requestConfig,
    processContent = (response) => response.data.payload,
    ...dialogPreProps
}: DialogPreEndpointProps) => {
    const { t } = useTranslation();
    const handleCatch = useHandleCatch();
    const [content, setContent] = React.useState<undefined | string>(undefined);
    const [error, setError] = React.useState<undefined | string>(undefined);
    const [loading, setLoading] = React.useState<boolean>(false);

    useDeepCompareEffectNoCheck(() => load(), [requestConfig, dialogPreProps.open]);

    const load = () => {
        if (typeof requestConfig === "undefined" || !dialogPreProps.open) {
            setContent(undefined);
            return;
        }
        setLoading(true);

        const axiosSource = axios.CancelToken.source();
        // requestConfig needs to be copied to avoid firing useDeepCompareEffectNoCheck
        const axiosRequestConfig = Object.assign({ cancelToken: axiosSource.token }, requestConfig);

        axios
            .request(axiosRequestConfig)
            .then((response: AxiosResponse) => {
                if (response.data.error) {
                    if (response.data.errorMessage) {
                        // Not sure what kind of problem TS sees here
                        setError(
                            t(
                                response.data.errorMessage,
                                response.data.errorMessageParameters ?? {}
                            ) as unknown as string
                        );
                    } else {
                        setError(t("error.unknownError"));
                    }
                } else {
                    setError(undefined);
                    setContent(processContent(response));
                }
                setLoading(false);
            })
            .catch((error: AxiosError) => {
                setLoading(false);
                handleCatch(error);
            });

        return () => {
            axiosSource.cancel(AXIOS_CANCELLED_UNMOUNTED);
        };
    };

    const children = (
        <>
            {error && <Alert severity="error">{error}</Alert>}
            {loading && <CircularLoader />}
        </>
    );

    return <DialogPre {...{ ...dialogPreProps, content: content, children: children }} />;
};

export default DialogPreEndpoint;
export { DialogPreEndpointProps };
