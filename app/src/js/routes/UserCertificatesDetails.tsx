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
import axios from "axios";
import { useHandleCatch, useLoader } from "@arteneo/forge";
import { Alert, Box } from "@mui/material";
import { ShieldOutlined } from "@mui/icons-material";
import { useTranslation } from "react-i18next";
import { UserInterface } from "~app/entities/User/definitions";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";
import CertificateDetails from "~app/components/Common/CertificateDetails";

const UserCertificatesDetails = () => {
    const { t } = useTranslation();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [user, setUser] = React.useState<undefined | UserInterface>(undefined);

    React.useEffect(() => load(), []);

    const load = () => {
        showLoader();

        axios
            .get("/usercertificate/certificates")
            .then((response) => {
                setUser(response.data);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });
    };

    if (typeof user === "undefined") {
        return null;
    }

    return (
        <>
            <SurfaceTitle {...{ title: "userCertificatesDetails.title", icon: <ShieldOutlined /> }} />
            <Box
                {...{
                    sx: {
                        display: "grid",
                        alignItems: "flex-start",
                        gridTemplateColumns: {
                            xs: "minmax(0, 1fr)",
                        },
                        gap: { xs: 2, lg: 4 },
                        mb: 2,
                    },
                }}
            >
                {user.useableCertificates?.length === 0 ? (
                    <Alert severity="warning">{t("userCertificatesDetails.noCertificatesAvailable")}</Alert>
                ) : (
                    <>
                        {user.useableCertificates?.map((useableCertificate, key) => (
                            <CertificateDetails
                                key={key}
                                {...{
                                    useableCertificate: useableCertificate,
                                    result: user,
                                    entityPrefix: "usercertificate",
                                }}
                            />
                        ))}
                    </>
                )}
            </Box>
        </>
    );
};

export default UserCertificatesDetails;
