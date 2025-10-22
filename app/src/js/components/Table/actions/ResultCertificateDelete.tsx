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
import { getIn } from "formik";
import _ from "lodash";
import { GppBadOutlined } from "@mui/icons-material";
import {
    ResultButtonDialogAlertConfirm,
    ResultButtonDialogAlertConfirmProps,
    Optional,
    DenyInterface,
} from "@arteneo/forge";
import EntityButtonInterface from "~app/definitions/EntityButtonInterface";
import { useDetails } from "~app/contexts/Details";
import { CertificateTypeProps } from "~app/definitions/CertificateTypeDefinitions";
import { isDenyHidden } from "~app/utilities/common";

type ResultCertificateDeleteProps = Optional<ResultButtonDialogAlertConfirmProps, "dialogProps"> &
    EntityButtonInterface &
    CertificateTypeProps;

const _denyKey = "deleteCertificate";
const isHidden = (deny?: DenyInterface) => isDenyHidden(_denyKey, ".accessDenied", deny);

const ResultCertificateDelete = ({
    result,
    path,
    dialogProps,
    entityPrefix,
    certificateTypeId,
    denyKey = _denyKey,
    deny,
    ...props
}: ResultCertificateDeleteProps) => {
    const { reload } = useDetails();

    if (typeof result === "undefined") {
        throw new Error("ResultCertificateDelete component: Missing required result prop");
    }

    if (isHidden(deny)) {
        return null;
    }

    const value = path ? getIn(result, path) : result;

    const internalDialogProps: ReturnType<ResultButtonDialogAlertConfirmProps["dialogProps"]> = {
        title: "certificateDelete.dialog.title",
        label: "certificateDelete.dialog.label",
        confirmProps: {
            label: "certificateDelete.action.deleteCertificate",
            color: "error",
            variant: "contained",
            endIcon: <GppBadOutlined />,
            endpoint: "/" + entityPrefix + "/" + value?.id + "/" + certificateTypeId + "/delete/certificate",
            snackbarLabel: "certificateDelete.snackbar.certificateDeleted",
            onSuccess: (defaultOnSuccess) => {
                defaultOnSuccess();
                reload();
            },
        },
    };

    return (
        <ResultButtonDialogAlertConfirm
            {...{
                result,
                deny,
                denyKey,
                label: "certificateDelete.action.deleteCertificate",
                color: "error",
                size: "small",
                variant: "contained",
                startIcon: <GppBadOutlined />,
                dialogProps: () =>
                    _.merge(internalDialogProps, typeof dialogProps !== "undefined" ? dialogProps(value) : {}),
                ...props,
            }}
        />
    );
};

export default ResultCertificateDelete;
export { ResultCertificateDeleteProps, isHidden };
