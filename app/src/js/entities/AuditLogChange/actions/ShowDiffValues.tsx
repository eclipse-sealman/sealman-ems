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
import { VisibilityOutlined } from "@mui/icons-material";
import { ButtonProps, ColumnActionInterface, GenericButtonDialog } from "@arteneo/forge";
import DialogDiffValues, { DialogDiffValuesProps } from "~app/entities/AuditLogChange/dialogs/DialogDiffValues";
import { humanizeEncryptedValues } from "~app/entities/AuditLogChange/encrypted";

type ShowNewValuesProps = ColumnActionInterface & ButtonProps;

const ShowDiffValues = ({ result, ...props }: ShowNewValuesProps) => {
    const { t } = useTranslation();

    if (typeof result === "undefined") {
        throw new Error("ShowDiffValues component: Missing required result prop");
    }

    if (result.type !== "update") {
        return null;
    }

    return (
        <GenericButtonDialog<DialogDiffValuesProps>
            {...{
                label: "auditLog.action.newValues",
                deny: result?.deny,
                color: "success",
                size: "small",
                variant: "contained",
                startIcon: <VisibilityOutlined />,
                ...props,
                component: DialogDiffValues,
                dialogProps: {
                    initializeEndpoint: "/auditlogchange/values/" + result.id,
                    onlyChanges: result.onlyChanges,
                    oldValues: (payload) =>
                        humanizeEncryptedValues(
                            payload?.oldValues ?? "{}",
                            t("auditLog.encryptedValue.unchanged"),
                            t("auditLog.encryptedValue.old")
                        ),
                    newValues: (payload) =>
                        humanizeEncryptedValues(
                            payload?.newValues ?? "{}",
                            t("auditLog.encryptedValue.unchanged"),
                            t("auditLog.encryptedValue.new")
                        ),
                },
            }}
        />
    );
};

export default ShowDiffValues;
export { ShowNewValuesProps };
