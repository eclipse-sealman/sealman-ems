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
import {
    Optional,
    BatchFormMultiAlert,
    BatchFormMultiAlertProps,
    mapRequestExecutionException,
    useHandleCatch,
    AXIOS_CANCELLED_UNMOUNTED,
} from "@arteneo/forge";
import { useUser } from "~app/contexts/User";
import { CertificateTypeInterface } from "~app/entities/Common/definitions";
import axios, { AxiosError, AxiosResponse } from "axios";
import BatchDevicesCertificateAutomaticBehaviorCollection from "~app/components/Form/fields/BatchDevicesCertificateAutomaticBehaviorCollection";
import { changeSubmitValuesBatchCertificateAutomaticBehaviorCollection } from "~app/components/Form/fields/CertificateAutomaticBehaviorCollection";

type BatchDisableProps = Optional<BatchFormMultiAlertProps, "dialogProps">;

const BatchDisable = (props: BatchDisableProps) => {
    const { isAccessGranted } = useUser();
    const handleCatch = useHandleCatch();
    const [useableCertificateTypes, setUseableCertificateTypes] = React.useState<
        undefined | CertificateTypeInterface[]
    >(undefined);

    let fields = {};
    //get list of ct with pliEn and scepAv
    if (isAccessGranted({ adminScep: true }) && useableCertificateTypes !== undefined) {
        fields = {
            certificateBehaviours: <BatchDevicesCertificateAutomaticBehaviorCollection {...{ enable: false }} />,
        };
    }

    React.useEffect(() => initializeuseableCertificateTypes(), []);

    const initializeuseableCertificateTypes = () => {
        if (!isAccessGranted({ adminScep: true })) {
            return;
        }

        const axiosSource = axios.CancelToken.source();
        axios
            .get("/device/certificate/types")
            .then((response: AxiosResponse) => {
                setUseableCertificateTypes(response.data);
            })
            .catch((error: AxiosError) => {
                handleCatch(error);
            });

        return () => {
            axiosSource.cancel(AXIOS_CANCELLED_UNMOUNTED);
        };
    };

    const hasUseableCertificateTypes = (): boolean => {
        if (Array.isArray(useableCertificateTypes)) {
            if (useableCertificateTypes.length > 0) {
                return true;
            }
        }
        return false;
    };

    return (
        <BatchFormMultiAlert
            {...{
                label: "batch.device.disable.action",
                ...props,
                dialogProps: {
                    title: "batch.device.disable.title",
                    label: hasUseableCertificateTypes()
                        ? "batch.device.disable.labelWithRevoke"
                        : "batch.device.disable.label",
                    formProps: {
                        resultDenyKey: "disable",
                        fields: fields,
                        initialValues: useableCertificateTypes ? { useableCertificates: useableCertificateTypes } : {},
                        endpoint: (result, values) => {
                            return {
                                url: "/device/" + result.id + "/disable",
                                data: changeSubmitValuesBatchCertificateAutomaticBehaviorCollection(
                                    values,
                                    result?.useableCertificates ?? []
                                ),
                            };
                        },
                        onSubmitCatchProcessResponse: ({ id, representation }, error) => {
                            const data = error?.response?.data;

                            if (error?.response?.status === 400) {
                                // Naive way of mapping when 400 error occurs
                                const error400Representative = data?.errors?.children?.revokeCertificate?.errors?.[0];
                                if (typeof error400Representative !== "undefined") {
                                    return {
                                        id: id,
                                        representation: representation,
                                        status: "error",
                                        messages: [
                                            {
                                                message: "dialogBatchResults.tooltip.errorMessage400",
                                                severity: "error",
                                            },
                                        ],
                                    };
                                }

                                return {
                                    id: id,
                                    representation: representation,
                                    status: "skipped",
                                    messages: [
                                        {
                                            message: "dialogBatchResults.tooltip.errorMessage400",
                                            severity: "error",
                                        },
                                    ],
                                };
                            }

                            if (error?.response?.status !== 409) {
                                return {
                                    id: id,
                                    representation: representation,
                                    status: "error",
                                    messages: [
                                        {
                                            message: "dialogBatchResults.tooltip.errorMessageUnexpected",
                                            severity: "error",
                                        },
                                    ],
                                };
                            }

                            return mapRequestExecutionException(id, representation, error.response.data);
                        },
                    },
                    ...props.dialogProps,
                },
            }}
        />
    );
};

export default BatchDisable;
export { BatchDisableProps };
