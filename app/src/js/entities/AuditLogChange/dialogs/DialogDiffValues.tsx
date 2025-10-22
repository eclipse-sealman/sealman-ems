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
import { Dialog, DialogProps, Optional, resolveDialogPayload, ResolveDialogPayloadType } from "@arteneo/forge";
import DialogDiffValuesContent from "~app/entities/AuditLogChange/dialogs/DialogDiffValuesContent";
import ButtonCopyToClipboard from "~app/components/Common/ButtonCopyToClipboard";

type InternalDialogDiffValuesProps = Optional<DialogProps, "children" | "title">;

interface DialogDiffValuesProps extends InternalDialogDiffValuesProps {
    oldValues: ResolveDialogPayloadType<string>;
    newValues: ResolveDialogPayloadType<string>;
    onlyChanges: boolean;
}

const DialogDiffValues = ({
    oldValues,
    newValues,
    onlyChanges,
    title = "auditLog.action.diffValues",
    dialogProps = {
        maxWidth: "lg",
    },
    onClose,
    ...props
}: DialogDiffValuesProps) => {
    return (
        <Dialog
            {...{
                children: (payload, initialized) => (
                    <DialogDiffValuesContent
                        {...{
                            oldValues: resolveDialogPayload<string>(oldValues, payload, initialized),
                            newValues: resolveDialogPayload<string>(newValues, payload, initialized),
                            onlyChanges,
                        }}
                    />
                ),
                actions: (payload, initialized) => (
                    <>
                        <ButtonCopyToClipboard
                            {...{
                                text: resolveDialogPayload<string>(oldValues, payload, initialized),
                                label: "auditLog.dialog.diffValues.action.copyOldValues",
                            }}
                        />
                        <ButtonCopyToClipboard
                            {...{
                                text: resolveDialogPayload<string>(newValues, payload, initialized),
                                label: "auditLog.dialog.diffValues.action.copyNewValues",
                            }}
                        />
                    </>
                ),
                title,
                dialogProps,
                onClose,
                ...props,
            }}
        />
    );
};

export default DialogDiffValues;
export { DialogDiffValuesProps };
