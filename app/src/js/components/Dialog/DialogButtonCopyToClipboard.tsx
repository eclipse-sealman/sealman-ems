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
import { useDialog, resolveDialogPayload, ResolveDialogPayloadType } from "@arteneo/forge";
import ButtonCopyToClipboard, { ButtonCopyToClipboardProps } from "~app/components/Common/ButtonCopyToClipboard";

interface DialogButtonCopyToClipboardProps extends Omit<ButtonCopyToClipboardProps, "text" | "content"> {
    content: ResolveDialogPayloadType<string>;
}

const DialogButtonCopyToClipboard = ({ content, ...props }: DialogButtonCopyToClipboardProps) => {
    const { payload, initialized, onClose } = useDialog();

    const resolvedContent = resolveDialogPayload<string>(content, payload, initialized);
    if (!resolvedContent) {
        return null;
    }

    return (
        <ButtonCopyToClipboard
            {...{
                text: resolvedContent,
                onCopy: (defaultOnCopy) => {
                    defaultOnCopy();
                    onClose();
                },
                ...props,
            }}
        />
    );
};

export default DialogButtonCopyToClipboard;
export { DialogButtonCopyToClipboardProps };
