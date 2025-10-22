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
import { Chip, ChipProps, Tooltip } from "@mui/material";
import { UseableCertificateEntityInterface, UseableCertificateInterface } from "~app/entities/Common/definitions";
import { Clear, HelpOutline } from "@mui/icons-material";
import { useTranslation } from "react-i18next";
import CertificateStats from "~app/components/Common/CertificateStats";
import { getUsableCertificateByCategory } from "~app/utilities/certificateType";
import { CertificateCategoryProps } from "~app/definitions/CertificateTypeDefinitions";

interface CertificateStatusProps extends CertificateCategoryProps {
    usableCertificateEntity?: UseableCertificateEntityInterface;
    useableCertificate?: UseableCertificateInterface;
    chipProps?: ChipProps;
}

const CertificateStatus = ({
    usableCertificateEntity,
    useableCertificate,
    certificateCategory = "deviceVpn",
    chipProps,
}: CertificateStatusProps) => {
    const { t } = useTranslation();

    if (!usableCertificateEntity) {
        return null;
    }

    let resolveduseableCertificate = useableCertificate;

    if (!resolveduseableCertificate) {
        resolveduseableCertificate = getUsableCertificateByCategory(usableCertificateEntity, certificateCategory);
    }

    if (!resolveduseableCertificate) {
        return null;
    }

    return (
        <>
            {resolveduseableCertificate.certificate.hasCertificate ? (
                <>
                    {resolveduseableCertificate.certificate.isCertificateExpired ? (
                        <Tooltip
                            title={<CertificateStats certificateObject={resolveduseableCertificate.certificate} />}
                        >
                            <Chip
                                {...{
                                    size: "small",
                                    color: "warning",
                                    label: t("certificate.expired"),
                                    icon: <HelpOutline color="warning" />,
                                    ...chipProps,
                                }}
                            />
                        </Tooltip>
                    ) : (
                        <Tooltip
                            title={<CertificateStats certificateObject={resolveduseableCertificate.certificate} />}
                        >
                            <Chip
                                {...{
                                    size: "small",
                                    color: "success",
                                    label: t("certificate.valid"),
                                    icon: <HelpOutline color="success" />,
                                    ...chipProps,
                                }}
                            />
                        </Tooltip>
                    )}
                </>
            ) : (
                <Chip
                    {...{
                        size: "small",
                        color: "error",
                        label: t("certificate.notAvailable"),
                        icon: <Clear color="error" />,
                        ...chipProps,
                    }}
                />
            )}
        </>
    );
};

export default CertificateStatus;
export { CertificateStatusProps };
