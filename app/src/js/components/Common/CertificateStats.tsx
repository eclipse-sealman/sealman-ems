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
import { CertificateInterface } from "~app/entities/Common/definitions";
import { useTranslation } from "react-i18next";
import { format } from "date-fns";
import { useUtils } from "@mui/x-date-pickers/internals";

interface CertificateStatsProps {
    certificateObject: CertificateInterface;
}

const CertificateStats = ({ certificateObject }: CertificateStatsProps) => {
    const { t } = useTranslation();
    const utils = useUtils();

    return (
        <>
            {t("certificate.certificateSubject")}:&nbsp;<strong>{certificateObject.certificateSubject}</strong>.<br />
            {t("certificate.certificateCaSubject")}:&nbsp;<strong>{certificateObject.certificateCaSubject}</strong>.
            <br />
            {t(
                certificateObject.isCertificateExpired
                    ? "certificate.certificateExpired"
                    : "certificate.certificateValid"
            )}
            :&nbsp;
            <strong>{format(utils.date(certificateObject.certificateValidTo) as Date, "dd-MM-yyyy HH:mm:ss")}</strong>
        </>
    );
};

export default CertificateStats;
export { CertificateStatsProps };
