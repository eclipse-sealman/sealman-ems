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

type ResultCertificateRevokeProps = Optional<ResultButtonDialogAlertConfirmProps, "dialogProps"> &
    EntityButtonInterface &
    CertificateTypeProps;

const _denyKey = "revokeCertificate";
const isHidden = (deny?: DenyInterface) =>
    isDenyHidden(_denyKey, ".accessDenied", deny) ||
    isDenyHidden(_denyKey, ".scepBlocked", deny) ||
    isDenyHidden(_denyKey, ".disabledInDeviceType", deny);

const ResultCertificateRevoke = ({
    result,
    path,
    dialogProps,
    entityPrefix,
    certificateTypeId,
    denyKey = _denyKey,
    deny,
    ...props
}: ResultCertificateRevokeProps) => {
    const { reload } = useDetails();

    if (typeof result === "undefined") {
        throw new Error("ResultCertificateRevoke component: Missing required result prop");
    }

    if (isHidden(deny)) {
        return null;
    }

    const value = path ? getIn(result, path) : result;

    const internalDialogProps: ReturnType<ResultButtonDialogAlertConfirmProps["dialogProps"]> = {
        title: "certificateRevoke.dialog.title",
        label: "certificateRevoke.dialog.label",
        confirmProps: {
            label: "certificateRevoke.action.revokeCertificate",
            color: "info",
            variant: "contained",
            endIcon: <GppBadOutlined />,
            endpoint: "/" + entityPrefix + "/" + result.id + "/" + certificateTypeId + "/revoke/certificate",
            snackbarLabel: "certificateRevoke.snackbar.certificateRevoked",
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
                label: "certificateRevoke.action.revokeCertificate",
                color: "info",
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

export default ResultCertificateRevoke;
export { ResultCertificateRevokeProps, isHidden };
