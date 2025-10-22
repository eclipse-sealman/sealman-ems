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

type ResultCertificateDownloadCaProps = Omit<ResultButtonDownloadProps, "endpoint"> &
    EntityButtonInterface &
    CertificateTypeProps;

const _denyKey = "downloadCaCertificate";
const isHidden = (deny?: DenyInterface) => isDenyHidden(_denyKey, ".accessDenied", deny);

const ResultCertificateDownloadCa = ({
    entityPrefix,
    certificateTypeId,
    denyKey = _denyKey,
    deny,
    ...props
}: ResultCertificateDownloadCaProps) => {
    if (isHidden(deny)) {
        return null;
    }

    return (
        <ResultButtonDownload
            {...{
                label: "certificateDownload.action.downloadCa",
                denyKey,
                deny,
                variant: "contained",
                color: "info",
                size: "small",
                startIcon: <PolicyOutlined />,
                endpoint: (result: ResultInterface) =>
                    "/" + entityPrefix + "/" + result.id + "/" + certificateTypeId + "/download/ca",
                ...props,
            }}
        />
    );
};

export default ResultCertificateDownloadCa;
export { ResultCertificateDownloadCaProps, isHidden };
