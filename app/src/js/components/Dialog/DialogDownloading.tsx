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
    Alert,
    Box,
    CircularProgress,
    Dialog,
    DialogActions,
    DialogContent,
    DialogProps,
    DialogTitle,
} from "@mui/material";
import { CloseOutlined } from "@mui/icons-material";
import { Button, ButtonProps, TranslateVariablesInterface, Optional } from "@arteneo/forge";

interface DialogDownloadingProps {
    open: boolean;
    onClose: () => void;
    buttonCancelProps?: ButtonProps;
    title?: string;
    titleVariables?: TranslateVariablesInterface;
    children?: React.ReactNode;
    label?: string;
    labelVariables?: TranslateVariablesInterface;
    dialogProps?: Optional<DialogProps, "open">;
}

const DialogDownloading = ({
    open,
    onClose,
    buttonCancelProps = {
        label: "action.cancel",
        variant: "contained",
        color: "error",
        startIcon: <CloseOutlined />,
    },
    title = "dialogDownloading.title",
    titleVariables = {},
    children,
    label = "dialogDownloading.label",
    labelVariables = {},
    dialogProps = {
        fullWidth: true,
        maxWidth: "sm",
    },
}: DialogDownloadingProps) => {
    const { t } = useTranslation();

    // Using DialogDownloadingProps typing definition that allows only label OR only children to be defined
    // gives missleading error when using none of them or both of them
    // Decided to go with Error throwing to make it easier for developers
    if (children === undefined && label === undefined) {
        throw new Error("DialogDownloading component: Missing children or label prop");
    }

    if (children !== undefined && label !== undefined) {
        throw new Error(
            "DialogDownloading component: It is not possible to use children and label prop at the same time"
        );
    }

    if (children === undefined && label !== undefined) {
        children = (
            <>
                <Alert {...{ severity: "info" }}>{t(label, labelVariables)}</Alert>
                <Box {...{ sx: { display: "flex", alignItems: "center", justifyContent: "center", pt: 3, px: 3 } }}>
                    <CircularProgress />
                </Box>
            </>
        );
    }

    return (
        <Dialog
            {...{
                open,
                onClose,
                ...dialogProps,
            }}
        >
            <DialogTitle>{t(title, titleVariables)}</DialogTitle>
            <DialogContent>{children}</DialogContent>
            <DialogActions {...{ sx: { justifyContent: "flex-start" } }}>
                <Button onClick={() => onClose()} {...buttonCancelProps} />
            </DialogActions>
        </Dialog>
    );
};

export default DialogDownloading;
export { DialogDownloadingProps };
