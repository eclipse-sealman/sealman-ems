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
import {
    ResultButtonDialogFormFieldset,
    ResultButtonDialogFormFieldsetProps,
    Optional,
    DenyInterface,
} from "@arteneo/forge";
import { AddModeratorOutlined } from "@mui/icons-material";
import EntityButtonInterface from "~app/definitions/EntityButtonInterface";
import getFields from "~app/fields/CertificateUploadFilesFields";
import { useDetails } from "~app/contexts/Details";
import { CertificateTypeProps } from "~app/definitions/CertificateTypeDefinitions";
import { isDenyHidden } from "~app/utilities/common";

type ResultCertificateUploadFilesProps = Optional<ResultButtonDialogFormFieldsetProps, "dialogProps"> &
    EntityButtonInterface &
    CertificateTypeProps;

const _denyKey = "uploadCertificates";
const isHidden = (deny?: DenyInterface) => isDenyHidden(_denyKey, ".accessDenied", deny);

const ResultCertificateUploadFiles = ({
    result,
    entityPrefix,
    certificateTypeId,
    denyKey = _denyKey,
    deny,
    ...props
}: ResultCertificateUploadFilesProps) => {
    const { reload } = useDetails();
    if (typeof result === "undefined") {
        throw new Error("ResultCertificateUploadFiles component: Missing required result prop");
    }

    if (isHidden(deny)) {
        return null;
    }

    return (
        <ResultButtonDialogFormFieldset
            {...{
                result,
                denyKey,
                deny,
                size: "small",
                startIcon: <AddModeratorOutlined />,
                label: "certificateUploadFiles.action.uploadCertFiles",
                color: "info",
                variant: "contained",
                dialogProps: (result) => ({
                    title: "certificateUploadFiles.dialog.title",
                    formProps: {
                        endpoint: "/" + entityPrefix + "/" + result.id + "/" + certificateTypeId + "/upload/files",
                        fields: getFields(),
                        onSubmitSuccess: (defaultOnSubmitSuccess) => {
                            defaultOnSubmitSuccess();
                            reload();
                        },
                    },
                }),
                ...props,
            }}
        />
    );
};

export default ResultCertificateUploadFiles;
export { ResultCertificateUploadFilesProps, isHidden };
