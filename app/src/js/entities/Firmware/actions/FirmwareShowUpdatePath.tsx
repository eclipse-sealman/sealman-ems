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
import { Optional, ResultButtonDialog, ResultButtonDialogProps, ResultInterface } from "@arteneo/forge";
import { getIn } from "formik";
import { ListOutlined } from "@mui/icons-material";
import TableFirmwareUpdatePath from "~app/components/Details/Template/TableFirmwareUpdatePath";

type FirmwareShowUpdatePathProps = Optional<ResultButtonDialogProps, "dialogProps">;

const FirmwareShowUpdatePath = ({ path, result, ...props }: FirmwareShowUpdatePathProps) => {
    const [showDialog, setShowDialog] = React.useState(false);

    if (typeof result === "undefined") {
        throw new Error("ResultDialogUrl component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;

    return (
        <ResultButtonDialog
            {...{
                label: "action.showUpdatePath",
                color: "success",
                size: "small",
                variant: "contained",
                startIcon: <ListOutlined />,
                deny: value?.deny,
                denyBehavior: "hide",
                denyKey: "showUpdatePath",
                open: showDialog,
                onClose: () => setShowDialog(false),
                result,
                dialogProps: (result: ResultInterface) => ({
                    dialogProps: {
                        maxWidth: "lg",
                    },
                    title: "firmwareUpdatePath.dialog.title",
                    children: <TableFirmwareUpdatePath firmwareId={result.id} />,
                }),
                ...props,
            }}
        />
    );
};

export default FirmwareShowUpdatePath;
export { FirmwareShowUpdatePathProps };
