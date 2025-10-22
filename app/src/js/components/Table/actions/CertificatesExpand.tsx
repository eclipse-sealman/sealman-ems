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
import { ShieldOutlined } from "@mui/icons-material";
import { DenyInterface } from "@arteneo/forge";
import ResultCertificateGenerate, {
    isHidden as isGenerateHidden,
} from "~app/components/Table/actions/ResultCertificateGenerate";
import ResultCertificateRevoke, {
    isHidden as isRevokeHidden,
} from "~app/components/Table/actions/ResultCertificateRevoke";
import ResultCertificateUploadPkcs12, {
    isHidden as isUploadPkcs12Hidden,
} from "~app/components/Table/actions/ResultCertificateUploadPkcs12";
import ResultCertificateUploadFiles, {
    isHidden as isUploadFilesHidden,
} from "~app/components/Table/actions/ResultCertificateUploadFiles";
import ResultCertificateDelete, {
    isHidden as isDeleteHidden,
} from "~app/components/Table/actions/ResultCertificateDelete";
import ResultCertificateDownloadCertificate, {
    isHidden as isDownloadCertificateHidden,
} from "~app/components/Table/actions/ResultCertificateDownloadCertificate";
import ResultCertificateDownloadKey, {
    isHidden as isDownloadKeyHidden,
} from "~app/components/Table/actions/ResultCertificateDownloadKey";
import ResultCertificateDownloadCa, {
    isHidden as isDownloadCaHidden,
} from "~app/components/Table/actions/ResultCertificateDownloadCa";
import ResultCertificateDownloadPkcs12, {
    isHidden as isDownloadPkcs12Hidden,
} from "~app/components/Table/actions/ResultCertificateDownloadPkcs12";
import ResultButtonExpand from "~app/components/Common/ResultButtonExpand";
import ResultButtonChildExpand from "~app/components/Common/ResultButtonChildExpand";
import EntityButtonInterface from "~app/definitions/EntityButtonInterface";
import { UseableCertificateEntityInterface } from "~app/entities/Common/definitions";

interface CertificatesExpandProps extends EntityButtonInterface {
    result?: UseableCertificateEntityInterface;
}

const CertificatesExpand = ({ result, entityPrefix }: CertificatesExpandProps) => {
    if (typeof result === "undefined") {
        throw new Error("CertificatesExpand component: Missing required result prop");
    }

    if (!result.useableCertificates) {
        return null;
    }

    const isVisible = (deny?: DenyInterface): boolean => {
        if (!isGenerateHidden(deny)) {
            return true;
        }

        if (!isRevokeHidden(deny)) {
            return true;
        }

        if (!isUploadFilesHidden(deny)) {
            return true;
        }

        if (!isUploadPkcs12Hidden(deny)) {
            return true;
        }

        if (!isDeleteHidden(deny)) {
            return true;
        }

        if (!isDownloadCertificateHidden(deny)) {
            return true;
        }

        if (!isDownloadKeyHidden(deny)) {
            return true;
        }

        if (!isDownloadCaHidden(deny)) {
            return true;
        }

        if (!isDownloadPkcs12Hidden(deny)) {
            return true;
        }

        return false;
    };

    // We have to determine here whether <ResultButtonChildExpand /> will have any children to avoid rendering it when empty
    const visibleUsableCertificates = result.useableCertificates.filter((useableCertificate) =>
        isVisible(useableCertificate.deny)
    );

    return (
        <ResultButtonExpand
            {...{
                result,
                label: "action.certificates",
                startIcon: <ShieldOutlined />,
                denyBehavior: "hide",
                denyKey: "certificateType",
                deny: result?.deny,
            }}
        >
            {visibleUsableCertificates.map((useableCertificate, key) => (
                <ResultButtonChildExpand
                    key={key}
                    {...{
                        result,
                        label: "action.pkiCertificate",
                        labelVariables: { certificateType: useableCertificate?.certificateType?.representation },
                    }}
                >
                    <ResultCertificateUploadFiles
                        {...{
                            entityPrefix: entityPrefix,
                            certificateTypeId: useableCertificate?.certificateType?.id,
                            deny: useableCertificate?.deny,
                        }}
                    />
                    <ResultCertificateUploadPkcs12
                        {...{
                            entityPrefix: entityPrefix,
                            certificateTypeId: useableCertificate?.certificateType?.id,
                            deny: useableCertificate?.deny,
                        }}
                    />
                    <ResultCertificateDelete
                        {...{
                            entityPrefix: entityPrefix,
                            certificateTypeId: useableCertificate?.certificateType?.id,
                            deny: useableCertificate?.deny,
                        }}
                    />
                    <ResultCertificateGenerate
                        {...{
                            entityPrefix: entityPrefix,
                            certificateTypeId: useableCertificate?.certificateType?.id,
                            deny: useableCertificate?.deny,
                        }}
                    />
                    <ResultCertificateRevoke
                        {...{
                            entityPrefix: entityPrefix,
                            certificateTypeId: useableCertificate?.certificateType?.id,
                            deny: useableCertificate?.deny,
                        }}
                    />
                    <ResultCertificateDownloadCertificate
                        {...{
                            entityPrefix: entityPrefix,
                            certificateTypeId: useableCertificate?.certificateType?.id,
                            deny: useableCertificate?.deny,
                        }}
                    />
                    <ResultCertificateDownloadKey
                        {...{
                            entityPrefix: entityPrefix,
                            certificateTypeId: useableCertificate?.certificateType?.id,
                            deny: useableCertificate?.deny,
                        }}
                    />
                    <ResultCertificateDownloadCa
                        {...{
                            entityPrefix: entityPrefix,
                            certificateTypeId: useableCertificate?.certificateType?.id,
                            deny: useableCertificate?.deny,
                        }}
                    />
                    <ResultCertificateDownloadPkcs12
                        {...{
                            entityPrefix: entityPrefix,
                            certificateTypeId: useableCertificate?.certificateType?.id,
                            deny: useableCertificate?.deny,
                        }}
                    />
                </ResultButtonChildExpand>
            ))}
        </ResultButtonExpand>
    );
};

export default CertificatesExpand;
export { CertificatesExpandProps };
