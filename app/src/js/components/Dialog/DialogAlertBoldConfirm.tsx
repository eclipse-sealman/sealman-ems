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
import DialogAlertBold, { DialogAlertBoldProps } from "~app/components/Dialog/DialogAlertBold";
import { CheckOutlined } from "@mui/icons-material";
import { DialogButtonEndpoint, DialogButtonEndpointProps, Optional } from "@arteneo/forge";

interface DialogAlertBoldConfirmProps extends Optional<DialogAlertBoldProps, "title"> {
    confirmProps: DialogButtonEndpointProps;
}

const DialogAlertBoldConfirm = ({ confirmProps, ...props }: DialogAlertBoldConfirmProps) => {
    return (
        <DialogAlertBold
            {...{
                title: "dialogAlertConfirm.title",
                actions: (
                    <DialogButtonEndpoint
                        {...{
                            label: "dialogAlertConfirm.confirm",
                            color: "success",
                            endIcon: <CheckOutlined />,
                            ...confirmProps,
                        }}
                    />
                ),
                ...props,
            }}
        />
    );
};

export default DialogAlertBoldConfirm;
export { DialogAlertBoldConfirmProps };
