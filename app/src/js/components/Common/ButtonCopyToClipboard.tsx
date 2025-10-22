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
import { ContentCopyOutlined } from "@mui/icons-material";
import CopyToClipBoard from "react-copy-to-clipboard";
import { Button, ButtonProps, TranslateVariablesInterface, useSnackbar } from "@arteneo/forge";

interface ButtonCopyToClipboardProps extends Omit<ButtonProps, "onCopy"> {
    text: string;
    snackbarLabel?: string;
    snackbarLabelVariables?: TranslateVariablesInterface;
    disableSnackbar?: boolean;
    onCopy?: (defaultOnCopy: () => void) => void;
}

const ButtonCopyToClipboard = ({
    text,
    snackbarLabel = "buttonCopyToClipboard.snackbar.success",
    snackbarLabelVariables = {},
    disableSnackbar = false,
    onCopy,
    label = "action.copyToClipBoard",
    variant = "contained",
    color = "info",
    endIcon = <ContentCopyOutlined />,
    ...props
}: ButtonCopyToClipboardProps) => {
    const { showSuccess } = useSnackbar();

    return (
        <CopyToClipBoard
            {...{
                text,
                onCopy: () => {
                    const defaultOnCopy = () => {
                        if (!disableSnackbar) {
                            showSuccess(snackbarLabel, snackbarLabelVariables);
                        }
                    };

                    if (typeof onCopy !== "undefined") {
                        onCopy(defaultOnCopy);
                        return;
                    }

                    defaultOnCopy();
                },
            }}
        >
            <Button {...{ label, variant, color, endIcon, ...props }} />
        </CopyToClipBoard>
    );
};

export default ButtonCopyToClipboard;
export { ButtonCopyToClipboardProps };
