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
import { Alert } from "@mui/material";
import { Box } from "@mui/system";
import { useUtils } from "@mui/x-date-pickers/internals";
import { differenceInDays, format } from "date-fns";
import { useTranslation } from "react-i18next";
import { useConfiguration } from "~app/contexts/Configuration";

const SsoCertificateNotification = () => {
    const { t } = useTranslation();
    const {
        microsoftOidcCredential,
        microsoftOidcGeneratedCertificatePublicValidTo,
        microsoftOidcUploadedCertificatePublicValidTo,
    } = useConfiguration();
    const utils = useUtils();

    let validTo: undefined | string = undefined;

    switch (microsoftOidcCredential) {
        case "certificateUpload":
            validTo = microsoftOidcUploadedCertificatePublicValidTo;
            break;
        case "certificateGenerate":
            validTo = microsoftOidcGeneratedCertificatePublicValidTo;
            break;
    }

    if (typeof validTo === "undefined") {
        return null;
    }

    const validToDate = utils.date(validTo);
    if (validToDate == "Invalid Date") {
        return null;
    }

    const daysDiff = differenceInDays(validToDate as Date, new Date());
    if (daysDiff > 30) {
        return null;
    }

    return (
        <Box {...{ sx: { mb: { xs: 1, md: 2 } } }}>
            <Alert severity="warning">
                {t("header.ssoCertificate.expireSoon", {
                    validTo: format(validToDate as Date, "dd-MM-yyyy HH:mm:ss"),
                })}
            </Alert>
        </Box>
    );
};

export default SsoCertificateNotification;
