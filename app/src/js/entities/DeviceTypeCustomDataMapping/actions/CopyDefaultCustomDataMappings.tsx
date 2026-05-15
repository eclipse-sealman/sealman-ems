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
import { ResultButtonDialogAlertConfirm, ResultButtonDialogAlertConfirmProps, Optional } from "@arteneo/forge";
import { ContentCopyOutlined } from "@mui/icons-material";
import { useDetails } from "~app/contexts/Details";
import { DeviceTypeInterface } from "~app/entities/DeviceType/definitions";
import { AxiosResponse } from "axios";

type CopyDefaultCustomDataMappingsProps = Optional<ResultButtonDialogAlertConfirmProps, "dialogProps"> & {
    deviceType: DeviceTypeInterface;
    onSuccess?: (
        defaultOnSuccess: () => void,
        response: AxiosResponse,
        setLoading: React.Dispatch<React.SetStateAction<boolean>>
    ) => void;
};

const CopyDefaultCustomDataMappings = ({ deviceType, onSuccess, ...props }: CopyDefaultCustomDataMappingsProps) => {
    const { reload } = useDetails();

    return (
        <ResultButtonDialogAlertConfirm
            {...{
                result: deviceType,
                size: "small",
                startIcon: <ContentCopyOutlined />,
                label: "action.copyDefaultCustomDataMappings",
                color: "info",
                variant: "contained",
                denyKey: "copyDefaultCustomDataMappings",
                deny: deviceType.deny,
                denyBehavior: "disable",
                dialogProps: (result) => ({
                    title: "resultDeviceTypeCopyDefaultCustomDataMappings.dialog.title",
                    label: "resultDeviceTypeCopyDefaultCustomDataMappings.dialog.label",
                    labelVariables: { deviceTypeRepresentation: result.representation },
                    confirmProps: {
                        endIcon: <ContentCopyOutlined />,
                        label: "action.copyDefaultCustomDataMappings",
                        color: "info",
                        variant: "contained",
                        endpoint: "/devicetype/" + result.id + "/copy/default/mappings",
                        snackbarLabel: "resultDeviceTypeCopyDefaultCustomDataMappings.snackbar.success",
                        onSuccess: (defaultOnSuccess, response, setLoading) => {
                            defaultOnSuccess();
                            reload();
                            if (onSuccess) {
                                onSuccess(defaultOnSuccess, response, setLoading);
                            }
                        },
                    },
                }),
                ...props,
            }}
        />
    );
};

export default CopyDefaultCustomDataMappings;
export { CopyDefaultCustomDataMappingsProps };
