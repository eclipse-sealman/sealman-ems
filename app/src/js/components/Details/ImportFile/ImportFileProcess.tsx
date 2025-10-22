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
import { Alert, Box, CircularProgress, LinearProgress, Typography } from "@mui/material";
import { useTranslation } from "react-i18next";
import { AXIOS_CANCELLED_UNMOUNTED, useHandleCatch } from "@arteneo/forge";
import { useNavigate } from "react-router-dom";
import axios, { AxiosError, CancelTokenSource } from "axios";
import { ImportFileInterface } from "~app/entities/ImportFile/definitions";
import Surface from "~app/components/Common/Surface";

interface ImportFileProcessProps {
    importFile: ImportFileInterface;
}

let axiosSource: null | CancelTokenSource = null;
let cancelled = false;

const ImportFileProcess = ({ importFile }: ImportFileProcessProps) => {
    const { t } = useTranslation();
    const handleCatch = useHandleCatch();
    const navigate = useNavigate();

    const [loading, setLoading] = React.useState(false);
    const [total, setTotal] = React.useState(0);
    const [pending, setPending] = React.useState(0);

    React.useEffect(() => load(), []);
    React.useEffect(() => {
        cancelled = false;
        return () => axiosSource?.cancel(AXIOS_CANCELLED_UNMOUNTED);
    }, []);

    const load = () => {
        setLoading(true);

        const axiosSource = axios.CancelToken.source();

        axios
            .get("/importfile/" + importFile.id + "/progress")
            .then((response) => {
                const progress = response.data;
                setTotal(progress.total);
                setPending(progress.pending);
                setLoading(false);
                start(progress.pending);
            })
            .catch((error) => {
                setLoading(false);
                handleCatch(error);
            });

        return () => {
            axiosSource.cancel(AXIOS_CANCELLED_UNMOUNTED);
        };
    };

    const importRows = async (pending: number) => {
        for (let i = 0; i < pending; i++) {
            if (cancelled) {
                return;
            }

            axiosSource = axios.CancelToken.source();

            await axios
                .get("/importfile/" + importFile.id + "/import/next/row", { cancelToken: axiosSource.token })
                .then(() => {
                    setPending((pending) => pending - 1);
                })
                // According to https://github.com/axios/axios/issues/3612
                // This should be typed as Error | AxiosError
                // Leaving this as it is to avoid further changes. Revisit when this will cause problems
                .catch((error: AxiosError) => {
                    if (error?.message === AXIOS_CANCELLED_UNMOUNTED) {
                        cancelled = true;
                    }

                    // In case of 403 we act as import finished successfully (this might be caused by opening multiple screens)
                    if (error?.response?.status === 403) {
                        navigate("/importfile/details/" + importFile.id);
                        return;
                    }

                    handleCatch(error);
                });
        }
    };

    const start = async (pending: number) => {
        await importRows(pending);

        if (!cancelled) {
            navigate("/importfile/details/" + importFile.id);
        }
    };

    const progress = Math.round(((total - pending) * 100) / total);

    return (
        <Surface>
            <Alert {...{ severity: "info" }}>{t("importFile.process.label")}</Alert>
            {loading ? (
                <Box
                    {...{
                        sx: {
                            display: "flex",
                            alignItems: "center",
                            justifyContent: "center",
                            minHeight: 100,
                            width: "100%",
                        },
                    }}
                >
                    <CircularProgress />
                </Box>
            ) : (
                <Box {...{ sx: { display: "flex", alignItems: "center", mt: 2 } }}>
                    <Box {...{ sx: { width: "100%", mr: 1 } }}>
                        <LinearProgress {...{ variant: "determinate", value: progress }} />
                    </Box>
                    <Box {...{ sx: { minWidth: 35 } }}>
                        <Typography variant="body2">{Math.round(progress)}%</Typography>
                    </Box>
                </Box>
            )}
        </Surface>
    );
};

export default ImportFileProcess;
export { ImportFileProcessProps };
