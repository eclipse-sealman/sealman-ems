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
import { PolicyOutlined } from "@mui/icons-material";
import { DenyInterface, ResultButtonDownload, ResultButtonDownloadProps, ResultInterface } from "@arteneo/forge";
import EntityButtonInterface from "~app/definitions/EntityButtonInterface";
import { CertificateTypeProps } from "~app/definitions/CertificateTypeDefinitions";
import { isDenyHidden } from "~app/utilities/common";

type ResultCertificateDownloadCertificateProps = Omit<ResultButtonDownloadProps, "endpoint"> &
    EntityButtonInterface &
    CertificateTypeProps;

const _denyKey = "downloadCertificate";
const isHidden = (deny?: DenyInterface) => isDenyHidden(_denyKey, ".accessDenied", deny);

const ResultCertificateDownloadCertificate = ({
    entityPrefix,
    certificateTypeId,
    denyKey = "downloadCertificate",
    deny,
    ...props
}: ResultCertificateDownloadCertificateProps) => {
    if (isHidden(deny)) {
        return null;
    }

    return (
        <ResultButtonDownload
            {...{
                label: "certificateDownload.action.downloadCertificate",
                denyKey,
                deny,
                variant: "contained",
                color: "info",
                size: "small",
                startIcon: <PolicyOutlined />,
                endpoint: (result: ResultInterface) =>
                    "/" + entityPrefix + "/" + result.id + "/" + certificateTypeId + "/download/certificate",
                ...props,
            }}
        />
    );
};

export default ResultCertificateDownloadCertificate;
export { ResultCertificateDownloadCertificateProps, isHidden };
