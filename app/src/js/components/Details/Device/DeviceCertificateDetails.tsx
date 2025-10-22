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
import { DeviceInterface } from "~app/entities/Device/definitions";
import CertificateDetails from "~app/components/Common/CertificateDetails";
import { AXIOS_CANCELLED_UNMOUNTED, useHandleCatch } from "@arteneo/forge";
import axios from "axios";
import { Box } from "@mui/system";
import { Alert, CircularProgress } from "@mui/material";
import { useTranslation } from "react-i18next";
import Surface from "~app/components/Common/Surface";

interface DeviceCertificateDetailsProps {
    device: DeviceInterface;
}

const DeviceCertificateDetails = ({ device }: DeviceCertificateDetailsProps) => {
    const { t } = useTranslation();
    const handleCatch = useHandleCatch();

    //Loading again because this endpoint will have certificate:private group enabled
    const [deviceCertificates, setDeviceCertificates] = React.useState<undefined | DeviceInterface>(undefined);
    const [loading, setLoading] = React.useState(true);

    React.useEffect(() => load(), []);

    const load = () => {
        setLoading(true);

        const axiosSource = axios.CancelToken.source();

        axios
            .get("/device/" + device.id + "/certificates")
            .then((response) => {
                setDeviceCertificates(response.data);
                setLoading(false);
            })
            .catch((error) => {
                setLoading(false);
                handleCatch(error);
            });

        return () => {
            axiosSource.cancel(AXIOS_CANCELLED_UNMOUNTED);
        };
    };

    if (loading) {
        return (
            <Box {...{ sx: { display: "flex", justifyContent: "center" } }}>
                <CircularProgress {...{ size: 32 }} />
            </Box>
        );
    }

    if (!deviceCertificates?.useableCertificates || deviceCertificates?.useableCertificates?.length === 0) {
        return (
            <Surface>
                <Alert severity="info">{t("deviceCertificatesDetails.noCertificatesAvailable")}</Alert>
            </Surface>
        );
    }

    return (
        <Box
            {...{
                sx: {
                    display: "grid",
                    alignItems: "flex-start",
                    gridTemplateColumns: {
                        xs: "minmax(0, 1fr)",
                        lg: "repeat(2, minmax(0,1fr))",
                    },
                    gap: { xs: 2, lg: 4 },
                    mb: 2,
                },
            }}
        >
            {deviceCertificates.useableCertificates?.map((useableCertificate, key) => (
                <CertificateDetails
                    key={key}
                    {...{
                        useableCertificate: useableCertificate,
                        result: device,
                        entityPrefix: "device",
                        size: "narrow",
                    }}
                />
            ))}
        </Box>
    );
};

export default DeviceCertificateDetails;
export { DeviceCertificateDetailsProps };
