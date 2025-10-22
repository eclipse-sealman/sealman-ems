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
import { LinkOutlined } from "@mui/icons-material";
import { useDialog, resolveDialogPayload, ResolveDialogPayloadType, Button, ButtonProps } from "@arteneo/forge";

interface DialogButtonRedirectProps extends ButtonProps {
    url: ResolveDialogPayloadType<string>;
}

const DialogButtonRedirect = ({ url, ...props }: DialogButtonRedirectProps) => {
    const { payload, initialized, onClose } = useDialog();

    const resolvedUrl = resolveDialogPayload<string>(url, payload, initialized);
    if (!resolvedUrl) {
        return null;
    }

    return (
        <Button
            {...{
                onClick: () => onClose(),
                component: "a",
                target: "_blank",
                href: resolvedUrl,
                label: "dialogButtonRedirect.action",
                variant: "contained",
                color: "info",
                endIcon: <LinkOutlined />,
                ...props,
            }}
        />
    );
};

export default DialogButtonRedirect;
export { DialogButtonRedirectProps };
