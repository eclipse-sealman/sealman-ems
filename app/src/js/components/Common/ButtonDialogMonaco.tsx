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
import DialogMonaco, { DialogMonacoProps } from "~app/components/Dialog/DialogMonaco";

interface ButtonDialogMonacoProps extends ButtonProps {
    dialogProps: Omit<DialogMonacoProps, "open" | "onClose">;
}

const ButtonDialogMonaco = ({ dialogProps, ...buttonProps }: ButtonDialogMonacoProps) => {
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

            <DialogMonaco
                {...{
                    open: showDialog,
                    onClose: () => setShowDialog(false),
                    ...dialogProps,
                }}
            />
        </>
    );
};

export default ButtonDialogMonaco;
export { ButtonDialogMonacoProps };
