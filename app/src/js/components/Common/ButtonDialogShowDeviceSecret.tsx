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
import { Button, ButtonProps } from "@arteneo/forge";
import { VisibilityOutlined } from "@mui/icons-material";
import DialogShowDeviceSecret, { DialogShowDeviceSecretProps } from "~app/components/Dialog/DialogShowDeviceSecret";

interface ButtonDialogShowDeviceSecretProps extends ButtonProps {
    dialogProps: Omit<DialogShowDeviceSecretProps, "open" | "onClose">;
}

const ButtonDialogShowDeviceSecret = ({ dialogProps, ...buttonProps }: ButtonDialogShowDeviceSecretProps) => {
    const [showDialog, setShowDialog] = React.useState(false);

    return (
        <>
            <Button
                {...{
                    label: "action.show",
                    color: "success",
                    size: "small",
                    variant: "contained",
                    startIcon: <VisibilityOutlined />,
                    onClick: () => setShowDialog(true),
                    ...buttonProps,
                }}
            />

            <DialogShowDeviceSecret
                {...{
                    open: showDialog,
                    onClose: () => setShowDialog(false),
                    ...dialogProps,
                }}
            />
        </>
    );
};

export default ButtonDialogShowDeviceSecret;
export { ButtonDialogShowDeviceSecretProps };
