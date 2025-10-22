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
import { Dialog, DialogProps, Optional } from "@arteneo/forge";
import DialogMonacoContent, { DialogMonacoContentProps } from "~app/components/Dialog/DialogMonacoContent";
import DialogButtonCopyToClipboard, {
    DialogButtonCopyToClipboardProps,
} from "~app/components/Dialog/DialogButtonCopyToClipboard";

type InternalDialogMonacoProps = Optional<DialogProps, "children" | "title"> & DialogMonacoContentProps;

interface DialogMonacoProps extends InternalDialogMonacoProps {
    copyToClipboardProps?: DialogButtonCopyToClipboardProps;
}

const DialogMonaco = ({
    content,
    language = "plain",
    disableFormatOnMount,
    monacoEditorProps = {},
    copyToClipboardProps,
    title = "dialogMonaco.title",
    dialogProps = {
        maxWidth: "lg",
    },
    onClose,
    ...props
}: DialogMonacoProps) => {
    return (
        <Dialog
            {...{
                children: <DialogMonacoContent {...{ content, language, disableFormatOnMount, monacoEditorProps }} />,
                actions: (
                    <DialogButtonCopyToClipboard
                        {...{
                            content,
                            snackbarLabel: "dialogMonaco.snackbar.copyToClipboardSuccess",
                            ...copyToClipboardProps,
                        }}
                    />
                ),
                title,
                dialogProps,
                onClose,
                ...props,
            }}
        />
    );
};

export default DialogMonaco;
export { DialogMonacoProps };
