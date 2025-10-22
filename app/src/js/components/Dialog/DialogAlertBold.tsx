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
import { Alert, AlertProps, Box } from "@mui/material";
import { useTranslation } from "react-i18next";
import {
    Optional,
    Dialog,
    DialogProps,
    resolveDialogPayload,
    TranslateVariablesInterface,
    ResolveDialogPayloadType,
    useDialog,
} from "@arteneo/forge";

interface DialogAlertBoldProps extends Optional<DialogProps, "children"> {
    label: ResolveDialogPayloadType<string>;
    labelVariables?: ResolveDialogPayloadType<TranslateVariablesInterface>;
    alertProps?: AlertProps;
    boldLabel?: ResolveDialogPayloadType<string>;
    boldLabelVariables?: ResolveDialogPayloadType<TranslateVariablesInterface>;
}

const DialogAlertBold = ({
    label,
    labelVariables = {},
    alertProps,
    boldLabel = "",
    boldLabelVariables = {},
    ...props
}: DialogAlertBoldProps) => {
    const { payload, initialized } = useDialog();
    const { t } = useTranslation();

    const resolvedLabel = resolveDialogPayload<string>(label, payload, initialized);
    const resolvedLabelVariables = resolveDialogPayload<TranslateVariablesInterface>(
        labelVariables,
        payload,
        initialized
    );

    const resolvedBoldLabel = resolveDialogPayload<string>(boldLabel, payload, initialized);
    const resolvedBoldLabelVariables = resolveDialogPayload<TranslateVariablesInterface>(
        boldLabelVariables,
        payload,
        initialized
    );

    return (
        <Dialog
            {...{
                children: (
                    <Alert {...{ severity: "info", ...alertProps }}>
                        {t(resolvedLabel, resolvedLabelVariables)}
                        {resolvedBoldLabel && (
                            <Box {...{ sx: { fontWeight: 700 } }}>
                                {t(resolvedBoldLabel, resolvedBoldLabelVariables)}
                            </Box>
                        )}
                    </Alert>
                ),
                ...props,
            }}
        />
    );
};

export default DialogAlertBold;
export { DialogAlertBoldProps };
