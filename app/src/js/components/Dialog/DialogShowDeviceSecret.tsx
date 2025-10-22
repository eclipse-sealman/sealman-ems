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
import DialogShowDeviceSecretContent from "~app/components/Dialog/DialogShowDeviceSecretContent";

type DialogShowDeviceSecretProps = Optional<DialogProps, "children" | "title">;

const DialogShowDeviceSecret = ({
    title = "dialogShowDeviceSecret.title",
    dialogProps = {
        maxWidth: "md",
    },
    onClose,
    ...props
}: DialogShowDeviceSecretProps) => {
    return (
        <Dialog
            {...{
                children: <DialogShowDeviceSecretContent />,
                title,
                dialogProps,
                onClose,
                ...props,
            }}
        />
    );
};

export default DialogShowDeviceSecret;
export { DialogShowDeviceSecretProps };
