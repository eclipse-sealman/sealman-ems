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
import { LinkOutlined } from "@mui/icons-material";
import DialogUrl, { DialogUrlProps } from "~app/components/Dialog/DialogUrl";

interface ButtonDialogUrlProps extends ButtonProps {
    dialogProps: Omit<DialogUrlProps, "open" | "onClose">;
}

const ButtonDialogUrl = ({ dialogProps, ...buttonProps }: ButtonDialogUrlProps) => {
    const [showDialog, setShowDialog] = React.useState(false);

    return (
        <>
            <Button
                {...{
                    label: "action.showUrl",
                    color: "success",
                    size: "small",
                    variant: "contained",
                    startIcon: <LinkOutlined />,
                    onClick: () => setShowDialog(true),
                    ...buttonProps,
                }}
            />

            <DialogUrl
                {...{
                    open: showDialog,
                    onClose: () => setShowDialog(false),
                    ...dialogProps,
                }}
            />
        </>
    );
};

export default ButtonDialogUrl;
export { ButtonDialogUrlProps };
