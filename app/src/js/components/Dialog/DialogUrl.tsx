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
import DialogUrlContent, { DialogUrlContentProps } from "~app/components/Dialog/DialogUrlContent";
import DialogButtonCopyToClipboard, {
    DialogButtonCopyToClipboardProps,
} from "~app/components/Dialog/DialogButtonCopyToClipboard";
import DialogButtonRedirect, { DialogButtonRedirectProps } from "~app/components/Dialog/DialogButtonRedirect";

type InternalDialogUrlProps = Optional<DialogProps, "children" | "title"> & DialogUrlContentProps;

interface DialogUrlProps extends InternalDialogUrlProps {
    copyToClipboardProps?: DialogButtonCopyToClipboardProps;
    redirectProps?: DialogButtonRedirectProps;
}

const DialogUrl = ({
    url,
    copyToClipboardProps,
    redirectProps,
    title = "dialogUrl.title",
    dialogProps = {
        maxWidth: "md",
    },
    onClose,
    ...props
}: DialogUrlProps) => {
    return (
        <Dialog
            {...{
                children: <DialogUrlContent {...{ url }} />,
                actions: (
                    <>
                        <DialogButtonCopyToClipboard
                            {...{
                                content: url,
                                snackbarLabel: "dialogUrl.snackbar.copyToClipboardSuccess",
                                ...copyToClipboardProps,
                            }}
                        />
                        <DialogButtonRedirect {...{ url, ...redirectProps }} />
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

export default DialogUrl;
export { DialogUrlProps };
