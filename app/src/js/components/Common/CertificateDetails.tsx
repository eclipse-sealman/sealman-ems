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
import { Alert, Box } from "@mui/material";
import { ResultInterface } from "@arteneo/forge";
import { useTranslation } from "react-i18next";
import DisplaySurface from "~app/components/Common/DisplaySurface";
import Display from "~app/components/Display/Display";
import EntityButtonInterface from "~app/definitions/EntityButtonInterface";
import { UseableCertificateInterface } from "~app/entities/Common/definitions";
import CertificateXsColumn from "~app/components/Table/columns/CertificateXsColumn";
import VariablePreColumn from "~app/components/Table/columns/VariablePreColumn";
import DateTimeSecondsColumn from "~app/components/Table/columns/DateTimeSecondsColumn";
import ResultCertificateDownloadCertificate from "~app/components/Table/actions/ResultCertificateDownloadCertificate";
import ResultCertificateDownloadKey from "~app/components/Table/actions/ResultCertificateDownloadKey";
import ResultCertificateDownloadCa from "~app/components/Table/actions/ResultCertificateDownloadCa";
import ResultCertificateDownloadPkcs12 from "~app/components/Table/actions/ResultCertificateDownloadPkcs12";

interface CertificateDetailsProps extends EntityButtonInterface {
    useableCertificate: UseableCertificateInterface;
    result: ResultInterface;
    size?: "wide" | "narrow";
}

const CertificateDetails = ({ useableCertificate, entityPrefix, result, size = "wide" }: CertificateDetailsProps) => {
    const { t } = useTranslation();

    const getHasCertificateData = (useableCertificate: UseableCertificateInterface): boolean => {
        if (
            useableCertificate?.certificate?.certificate ||
            useableCertificate?.certificate?.certificateCa ||
            useableCertificate?.certificate?.privateKey
        ) {
            return true;
        }
        return false;
    };

    const getRows = (useableCertificate: UseableCertificateInterface) => {
        return {
            certificateStatus: <CertificateXsColumn useableCertificate={useableCertificate} />,
            certificateSubject: <VariablePreColumn {...{ result: useableCertificate.certificate }} />,
            certificateValidTo: <DateTimeSecondsColumn {...{ result: useableCertificate.certificate }} />,
            certificate: <VariablePreColumn {...{ result: useableCertificate.certificate }} />,
            privateKey: <VariablePreColumn {...{ result: useableCertificate.certificate }} />,
            certificateCa: <VariablePreColumn {...{ result: useableCertificate.certificate }} />,
        };
    };
    return (
        <DisplaySurface
            {...{
                title: "certificatesDetails.certificateTypeName",
                titleVariables: {
                    certificateTypeName: useableCertificate.certificateType.representation,
                },
            }}
        >
            {getHasCertificateData(useableCertificate) ? (
                <>
                    <Display
                        {...{
                            result: useableCertificate.certificate,
                            rows: getRows(useableCertificate),
                        }}
                    />
                    <Box
                        {...{
                            sx: {
                                display: "grid",
                                alignItems: "flex-start",
                                gridTemplateColumns: {
                                    xs: "none",
                                    sm: "repeat(2, minmax(0,1fr))",
                                    xl: size === "wide" ? "repeat(4, minmax(0,1fr))" : "repeat(2, minmax(0,1fr))",
                                },
                                gap: 2,
                                mt: 2,
                            },
                        }}
                    >
                        <ResultCertificateDownloadCertificate
                            {...{
                                result: result,
                                entityPrefix: entityPrefix,
                                certificateTypeId: useableCertificate?.certificateType?.id,
                                deny: useableCertificate?.deny,
                            }}
                        />
                        <ResultCertificateDownloadKey
                            {...{
                                result: result,
                                entityPrefix: entityPrefix,
                                certificateTypeId: useableCertificate?.certificateType?.id,
                                deny: useableCertificate?.deny,
                            }}
                        />
                        <ResultCertificateDownloadCa
                            {...{
                                result: result,
                                entityPrefix: entityPrefix,
                                certificateTypeId: useableCertificate?.certificateType?.id,
                                deny: useableCertificate?.deny,
                            }}
                        />
                        <ResultCertificateDownloadPkcs12
                            {...{
                                result: result,
                                entityPrefix: entityPrefix,
                                certificateTypeId: useableCertificate?.certificateType?.id,
                                deny: useableCertificate?.deny,
                            }}
                        />
                    </Box>
                </>
            ) : (
                <Alert severity="info">{t("certificatesDetails.certificateNotAvailable")}</Alert>
            )}
        </DisplaySurface>
    );
};

export default CertificateDetails;
export { CertificateDetailsProps };
