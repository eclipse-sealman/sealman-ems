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
import { HealthAndSafetyOutlined } from "@mui/icons-material";
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

type ResultCertificateGenerateProps = Optional<ResultButtonDialogAlertConfirmProps, "dialogProps"> &
    EntityButtonInterface &
    CertificateTypeProps;

const _denyKey = "generateCertificate";
const isHidden = (deny?: DenyInterface) =>
    isDenyHidden(_denyKey, ".accessDenied", deny) ||
    isDenyHidden(_denyKey, ".scepBlocked", deny) ||
    isDenyHidden(_denyKey, ".disabledInDeviceType", deny);

const ResultCertificateGenerate = ({
    result,
    path,
    dialogProps,
    entityPrefix,
    denyKey = _denyKey,
    deny,
    certificateTypeId,
    ...props
}: ResultCertificateGenerateProps) => {
    const { reload } = useDetails();

    if (typeof result === "undefined") {
        throw new Error("ResultCertificateGenerate component: Missing required result prop");
    }

    if (isHidden(deny)) {
        return null;
    }

    const value = path ? getIn(result, path) : result;

    const internalDialogProps: ReturnType<ResultButtonDialogAlertConfirmProps["dialogProps"]> = {
        title: "certificateGenerate.dialog.title",
        label: "certificateGenerate.dialog.label",
        confirmProps: {
            label: "certificateGenerate.action.generateCertificate",
            color: "info",
            variant: "contained",
            endIcon: <HealthAndSafetyOutlined />,
            endpoint: "/" + entityPrefix + "/" + value?.id + "/" + certificateTypeId + "/generate/certificate",
            snackbarLabel: "certificateGenerate.snackbar.certificateGenerated",
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
                denyKey,
                deny,
                label: "certificateGenerate.action.generateCertificate",
                color: "info",
                size: "small",
                variant: "contained",
                startIcon: <HealthAndSafetyOutlined />,
                dialogProps: () =>
                    _.merge(internalDialogProps, typeof dialogProps !== "undefined" ? dialogProps(value) : {}),
                ...props,
            }}
        />
    );
};

export default ResultCertificateGenerate;
export { ResultCertificateGenerateProps, isHidden };
