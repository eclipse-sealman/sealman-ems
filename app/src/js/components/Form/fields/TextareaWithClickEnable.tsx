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
import {
    IconButtonDialogAlert,
    DialogButtonClose,
    Textarea,
    TextareaProps,
    TranslateVariablesInterface,
} from "@arteneo/forge";
import { EditOutlined } from "@mui/icons-material";

interface TextareaWithClickEnableProps extends TextareaProps {
    confirmationLabel: string;
    confirmationLabelVariables?: TranslateVariablesInterface;
}

const TextareaWithClickEnable = ({
    confirmationLabel,
    confirmationLabelVariables = {},
    ...textareaProps
}: TextareaWithClickEnableProps) => {
    const [disabled, setDisabled] = React.useState<boolean>(true);

    return (
        <Textarea
            {...{
                fieldProps: {
                    InputProps: {
                        endAdornment: (
                            <IconButtonDialogAlert
                                {...{
                                    icon: <EditOutlined />,
                                    dialogProps: {
                                        title: "configuration.vpn.dialogTitle",
                                        label: confirmationLabel,
                                        labelVariables: confirmationLabelVariables,
                                        actions: (
                                            <DialogButtonClose
                                                {...{
                                                    label: "action.confirm",
                                                    variant: "contained",
                                                    color: "success",
                                                    startIcon: null,
                                                    endIcon: <EditOutlined />,
                                                    onClick: (onClose) => {
                                                        setDisabled(false);
                                                        onClose();
                                                    },
                                                }}
                                            />
                                        ),
                                    },
                                }}
                            />
                        ),
                    },
                },
                ...textareaProps,
                disabled: disabled,
            }}
        />
    );
};

export default TextareaWithClickEnable;
export { TextareaWithClickEnableProps };
