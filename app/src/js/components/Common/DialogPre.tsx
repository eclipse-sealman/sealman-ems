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
import {
    Dialog as MuiDialog,
    DialogActions,
    DialogContent,
    DialogProps as MuiDialogProps,
    DialogTitle,
    Tooltip,
} from "@mui/material";
import { Close, ContentCopy } from "@mui/icons-material";
import { Button, ButtonProps, Optional, TranslateVariablesInterface, useSnackbar } from "@arteneo/forge";
import Pre, { PreProps } from "~app/components/Common/Pre";
import CopyToClipBoard from "react-copy-to-clipboard";

interface DialogPreProps {
    open: boolean;
    onClose: () => void;
    buttonBackProps?: ButtonProps;
    buttonCopyToClipboardProps?: ButtonProps;
    title: string;
    titleVariables?: TranslateVariablesInterface;
    snackbarLabel?: string;
    snackbarLabelVariables?: TranslateVariablesInterface;
    contentNotProvidedLabel?: string;
    content?: string;
    children?: React.ReactNode;
    dialogProps?: Optional<MuiDialogProps, "open">;
    preProps?: Omit<PreProps, "content">;
}

const DialogPre = ({
    open,
    onClose,
    buttonBackProps = {
        label: "action.close",
        variant: "outlined",
        color: "warning",
        startIcon: <Close />,
    },
    buttonCopyToClipboardProps = {
        label: "action.copyToClipBoard",
        variant: "outlined",
        color: "primary",
        endIcon: <ContentCopy />,
    },
    snackbarLabel = "dialogPre.snackbar.copyToClipboardSuccess",
    snackbarLabelVariables = {},
    contentNotProvidedLabel = "dialogPre.contentNotPovided",
    title,
    titleVariables = {},
    content,
    children,
    dialogProps = {
        fullWidth: true,
        maxWidth: "sm",
    },
    preProps,
}: DialogPreProps) => {
    const { t } = useTranslation();
    const { showSuccess } = useSnackbar();

    return (
        <MuiDialog
            {...{
                open,
                onClose,
                ...dialogProps,
            }}
        >
            <DialogTitle>{t(title, titleVariables)}</DialogTitle>
            <DialogContent>
                <>
                    {content && <Pre {...{ content, ...preProps }} />}
                    {children}
                </>
            </DialogContent>
            <DialogActions {...{ sx: { justifyContent: "space-between" } }}>
                <Button onClick={() => onClose()} {...buttonBackProps} />
                {content ? (
                    <CopyToClipBoard
                        onCopy={() => {
                            if (snackbarLabel) {
                                showSuccess(snackbarLabel, snackbarLabelVariables);
                            }
                        }}
                        text={content}
                    >
                        <Button {...buttonCopyToClipboardProps} />
                    </CopyToClipBoard>
                ) : (
                    <Tooltip title={t(contentNotProvidedLabel) ?? ""}>
                        <span>
                            <Button disabled {...buttonCopyToClipboardProps} />
                        </span>
                    </Tooltip>
                )}
            </DialogActions>
        </MuiDialog>
    );
};

export default DialogPre;
export { DialogPreProps };
