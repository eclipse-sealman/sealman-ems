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
import { getIn } from "formik";
import { Button, ColumnActionPathInterface } from "@arteneo/forge";
import ButtonDialogShowDeviceSecret, {
    ButtonDialogShowDeviceSecretProps,
} from "~app/components/Common/ButtonDialogShowDeviceSecret";
import { RemoveRedEyeOutlined } from "@mui/icons-material";
import DialogShowDeviceSecretVariables from "~app/components/Dialog/DialogShowDeviceSecretVariables";

type ResultDialogShowVariablesDeviceSecretProps = Omit<ButtonDialogShowDeviceSecretProps, "dialogProps"> &
    ColumnActionPathInterface;

const ResultDialogShowVariablesDeviceSecret = ({
    result,
    path,
    ...props
}: ResultDialogShowVariablesDeviceSecretProps) => {
    const [showDialog, setShowDialog] = React.useState(false);

    if (typeof result === "undefined") {
        throw new Error("ResultDialogShowVariablesDeviceSecret component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;

    if (value?.deviceTypeSecret?.useAsVariable !== true) {
        return (
            <Button
                {...{
                    color: "success",
                    size: "small",
                    variant: "contained",
                    label: "action.showSecretVariables",
                    denyKey: "showVariables",
                    denyBehavior: "disable",
                    startIcon: <RemoveRedEyeOutlined />,
                    deny: { showVariables: "deny.deviceSecret.showVariablesDisabledUseAsVariable" },
                    ...props,
                }}
            />
        );
    }

    return (
        <ButtonDialogShowDeviceSecret
            {...{
                label: "action.showSecretVariables",
                denyKey: "showVariables",
                denyBehavior: "hide",
                icon: <RemoveRedEyeOutlined />,
                dialogProps: {
                    title: "deviceSecret.dialogDeviceSecretVariables.title",
                    titleVariables: {
                        deviceSecretName: value?.deviceTypeSecret?.name,
                    },
                    initializeEndpoint:
                        "/devicesecret/" + value?.deviceTypeSecret?.id + "/show/variables/" + value?.device?.id,
                    children: <DialogShowDeviceSecretVariables />,
                },
                deny: value?.deny,
                open: showDialog,
                onClose: () => setShowDialog(false),
                ...props,
            }}
        />
    );
};

export default ResultDialogShowVariablesDeviceSecret;
export { ResultDialogShowVariablesDeviceSecretProps };
