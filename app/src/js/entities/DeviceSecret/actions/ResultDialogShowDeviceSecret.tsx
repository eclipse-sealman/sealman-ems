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
import { ColumnActionPathInterface } from "@arteneo/forge";
import ButtonDialogShowDeviceSecret, {
    ButtonDialogShowDeviceSecretProps,
} from "~app/components/Common/ButtonDialogShowDeviceSecret";
import { RemoveRedEyeOutlined } from "@mui/icons-material";

type ResultDialogShowDeviceSecretProps = Omit<ButtonDialogShowDeviceSecretProps, "dialogProps"> &
    ColumnActionPathInterface;

const ResultDialogShowDeviceSecret = ({ result, path, ...props }: ResultDialogShowDeviceSecretProps) => {
    const [showDialog, setShowDialog] = React.useState(false);

    if (typeof result === "undefined") {
        throw new Error("ResultDialogShowDeviceSecret component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;

    return (
        <ButtonDialogShowDeviceSecret
            {...{
                label: "action.showSecretValue",
                denyKey: "show",
                denyBehavior: "hide",
                icon: <RemoveRedEyeOutlined />,
                dialogProps: {
                    title: "deviceSecret.dialog.title",
                    titleVariables: {
                        deviceSecretName: value?.deviceTypeSecret?.name,
                    },
                    initializeEndpoint: "/devicesecret/" + value?.id + "/show",
                },
                ...props,

                deny: value?.deny,
                open: showDialog,
                onClose: () => setShowDialog(false),
                ...props,
            }}
        />
    );
};

export default ResultDialogShowDeviceSecret;
export { ResultDialogShowDeviceSecretProps };
