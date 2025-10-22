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
import { useTranslation } from "react-i18next";
import { ButtonProps, ColumnActionInterface } from "@arteneo/forge";
import ResultDialogMonaco from "~app/components/Table/actions/ResultDialogMonaco";
import { humanizeEncryptedValues } from "~app/entities/AuditLogChange/encrypted";

type ShowNewValuesProps = ColumnActionInterface & ButtonProps;

const ShowNewValues = ({ result, ...props }: ShowNewValuesProps) => {
    const { t } = useTranslation();

    if (typeof result === "undefined") {
        throw new Error("ShowNewValues component: Missing required result prop");
    }

    if (result.type !== "create") {
        return null;
    }

    return (
        <ResultDialogMonaco
            {...{
                label: "auditLog.action.newValues",
                result,
                dialogProps: (result) => ({
                    initializeEndpoint: "/auditlogchange/values/" + result.id,
                    content: (payload) =>
                        humanizeEncryptedValues(
                            payload?.newValues ?? "N/A",
                            t("auditLog.encryptedValue.unchanged"),
                            t("auditLog.encryptedValue.new")
                        ),
                    language: "json",
                }),
                ...props,
            }}
        />
    );
};

export default ShowNewValues;
export { ShowNewValuesProps };
