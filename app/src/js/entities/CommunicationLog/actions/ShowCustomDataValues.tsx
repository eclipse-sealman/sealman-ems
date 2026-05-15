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
import { Optional, ResultButtonDialog, ResultButtonDialogProps } from "@arteneo/forge";
import DialogShowCustomDataValues from "~app/components/Dialog/DialogShowCustomDataValues";
import { DataObjectOutlined } from "@mui/icons-material";

type ShowCustomDataValuesProps = Optional<ResultButtonDialogProps, "dialogProps">;

const ShowCustomDataValues = ({ result, ...props }: ShowCustomDataValuesProps) => {
    if (typeof result === "undefined") {
        throw new Error("ShowCustomDataValues component: Missing required result prop");
    }

    return (
        <ResultButtonDialog
            {...{
                result,
                denyKey: "customDataValues",
                denyBehavior: "hide",
                label: "resultDialogShowCustomDataValues.action",
                color: "info",
                size: "small",
                variant: "contained",
                startIcon: <DataObjectOutlined />,
                dialogProps: (result) => ({
                    title: "resultDialogShowCustomDataValues.dialog.title",
                    initializeEndpoint: "/communicationlog/" + result.id + "/custom/data/values",
                    children: <DialogShowCustomDataValues />,
                }),
                ...props,
            }}
        />
    );
};

export default ShowCustomDataValues;
export { ShowCustomDataValuesProps };
