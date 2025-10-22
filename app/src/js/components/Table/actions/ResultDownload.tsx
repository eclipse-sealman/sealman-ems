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
import axios, { CancelTokenSource } from "axios";
import { DownloadOutlined } from "@mui/icons-material";
import {
    Button,
    ButtonProps,
    useLoader,
    EndpointType,
    ResultResolveType,
    ColumnActionPathInterface,
    resolveAnyOrFunction,
    resolveEndpoint,
    AXIOS_CANCELLED_UNMOUNTED,
    useSnackbar,
    TranslateVariablesInterface,
} from "@arteneo/forge";
import { getIn } from "formik";
import DialogDownloading from "~app/components/Dialog/DialogDownloading";
import { applyAuthenticationInterceptor } from "~app/utilities/authentication";

interface ResultDownloadRenderDialogParams {
    open: boolean;
    onClose: () => void;
}

interface ResultDownloadSpecificProps {
    endpoint: ResultResolveType<EndpointType>;
    snackbarErrorLabel?: string;
    snackbarErrorLabelVariables?: TranslateVariablesInterface;
    renderDialog?: (params: ResultDownloadRenderDialogParams) => React.ReactNode;
}

type ResultDownloadProps = Omit<ButtonProps, "endpoint"> & ColumnActionPathInterface & ResultDownloadSpecificProps;

// TODO Arek This component could potentially also be refactor to new dialogs. Figure out what is its purpose
const ResultDownload = ({
    endpoint,
    snackbarErrorLabel = "resultDownload.snackbar.error",
    snackbarErrorLabelVariables = {},
    renderDialog = (params) => <DialogDownloading {...{ ...params }} />,
    result,
    path,
    ...props
}: ResultDownloadProps) => {
    const { showLoader, hideLoader } = useLoader();
    const { showError } = useSnackbar();
    const [downloading, setDownloading] = React.useState(false);
    const [downloadingCancelTokenSource, setDownloadingCancelTokenSource] = React.useState<
        undefined | CancelTokenSource
    >(undefined);

    if (typeof result === "undefined") {
        throw new Error("ResultButtonDownload component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;
    const resolvedEndpoint: EndpointType = resolveAnyOrFunction(endpoint, value, result, path);

    const requestConfig = resolveEndpoint(resolvedEndpoint);
    if (typeof requestConfig === "undefined") {
        return null;
    }

    const onCloseDownloading = () => {
        hideLoader();
        setDownloading(false);

        if (downloadingCancelTokenSource) {
            downloadingCancelTokenSource.cancel(AXIOS_CANCELLED_UNMOUNTED);
            setDownloadingCancelTokenSource(undefined);
        }
    };

    const onClick = () => {
        if (downloading) {
            return;
        }

        setDownloading(true);
        showLoader();

        const axiosSource = axios.CancelToken.source();
        setDownloadingCancelTokenSource(axiosSource);

        requestConfig.responseType = requestConfig.responseType ?? "blob";
        requestConfig.cancelToken = axiosSource.token;

        // Different instance needs to be used this way to disable axios prefix
        const axiosInstance = axios.create({
            baseURL: "",
        });
        applyAuthenticationInterceptor(axiosInstance);

        axiosInstance
            .request(requestConfig)
            .then((response) => {
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement("a");
                link.href = url;
                let filename = "undefined";

                const filenameMatch = response.headers["content-disposition"].match(/filename="([^"]+)"/);
                if (filenameMatch[1]) {
                    filename = filenameMatch[1];
                }

                link.setAttribute("download", filename);
                link.setAttribute("target", "_blank");
                document.body.appendChild(link);
                link.click();

                setDownloading(false);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                setDownloading(false);
                showError(
                    snackbarErrorLabel,
                    Object.assign(
                        { status: error.response.status, statusText: error.response.statusText },
                        snackbarErrorLabelVariables
                    )
                );
            });
    };

    return (
        <>
            <Button
                {...{
                    onClick: () => onClick(),
                    label: "action.download",
                    color: "success",
                    size: "small",
                    variant: "contained",
                    startIcon: <DownloadOutlined />,
                    denyKey: "download",
                    denyBehavior: "hide",
                    deny: result?.deny,
                    ...props,
                }}
            />

            {renderDialog({
                open: downloading,
                onClose: () => onCloseDownloading(),
            })}
        </>
    );
};

export default ResultDownload;
export { ResultDownloadProps };
