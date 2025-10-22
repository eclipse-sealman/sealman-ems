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
import DialogAlertBoldConfirm, { DialogAlertBoldConfirmProps } from "~app/components/Dialog/DialogAlertBoldConfirm";

interface ButtonDialogAlertBoldConfirmProps extends ButtonProps {
    dialogProps: Omit<DialogAlertBoldConfirmProps, "open" | "onClose">;
}

const ButtonDialogAlertBoldConfirm = ({ dialogProps, ...buttonProps }: ButtonDialogAlertBoldConfirmProps) => {
    const [showDialog, setShowDialog] = React.useState(false);

    return (
        <>
            <Button
                {...{
                    onClick: () => setShowDialog(true),
                    ...buttonProps,
                }}
            />

            <DialogAlertBoldConfirm
                {...{
                    open: showDialog,
                    onClose: () => setShowDialog(false),
                    ...dialogProps,
                }}
            />
        </>
    );
};

export default ButtonDialogAlertBoldConfirm;
export { ButtonDialogAlertBoldConfirmProps };
