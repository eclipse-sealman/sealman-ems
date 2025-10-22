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
import _ from "lodash";
import { PrecisionManufacturingOutlined } from "@mui/icons-material";
import {
    ResultButtonDialogFormAlertFieldset,
    ResultButtonDialogFormAlertFieldsetProps,
    Optional,
    Checkbox,
} from "@arteneo/forge";
import { useDetails } from "~app/contexts/Details";
import { useDeviceType } from "~app/contexts/DeviceType";

type SelectTemplateVersionSpecificProps = {
    actionLabel: string;
    denyKey: string;
    snackbarLabel: string;
    dialogTitle: string;
    dialogLabel: string;
    // eslint-disable-next-line
    endpoint: (value: any) => string;
};

type SelectTemplateVersionProps = SelectTemplateVersionSpecificProps &
    Optional<ResultButtonDialogFormAlertFieldsetProps, "dialogProps">;

// Base component for SelectStaging and SelectProduction - to be used only with templateVersion tables - created for code reusability
// * Note! It uses useDeviceType() context to avoid unecessary request
const SelectTemplateVersion = ({
    actionLabel,
    denyKey,
    snackbarLabel,
    dialogTitle,
    dialogLabel,
    endpoint,
    result,
    path,
    dialogProps,
    ...props
}: SelectTemplateVersionProps) => {
    const { reload } = useDetails();
    const deviceType = useDeviceType();

    if (typeof result === "undefined") {
        throw new Error("SelectTemplateVersion component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;

    const fields = {
        reinstallConfig1: (
            <Checkbox
                {...{
                    label: "reinstallConfig",
                    labelVariables: { config: deviceType?.nameConfig1 },
                    hidden: !deviceType?.hasConfig1 || deviceType?.hasAlwaysReinstallConfig1,
                }}
            />
        ),
        reinstallConfig2: (
            <Checkbox
                {...{
                    label: "reinstallConfig",
                    labelVariables: { config: deviceType?.nameConfig2 },
                    hidden: !deviceType?.hasConfig2 || deviceType?.hasAlwaysReinstallConfig2,
                }}
            />
        ),
        reinstallConfig3: (
            <Checkbox
                {...{
                    label: "reinstallConfig",
                    labelVariables: { config: deviceType?.nameConfig3 },
                    hidden: !deviceType?.hasConfig3 || deviceType?.hasAlwaysReinstallConfig3,
                }}
            />
        ),
        reinstallFirmware1: (
            <Checkbox
                {...{
                    label: "reinstallFirmware",
                    labelVariables: { firmware: deviceType?.nameFirmware1 },
                    hidden: !deviceType?.hasFirmware1,
                }}
            />
        ),
        reinstallFirmware2: (
            <Checkbox
                {...{
                    label: "reinstallFirmware",
                    labelVariables: { firmware: deviceType?.nameFirmware2 },
                    hidden: !deviceType?.hasFirmware2,
                }}
            />
        ),
        reinstallFirmware3: (
            <Checkbox
                {...{
                    label: "reinstallFirmware",
                    labelVariables: { firmware: deviceType?.nameFirmware3 },
                    hidden: !deviceType?.hasFirmware3,
                }}
            />
        ),
    };

    const internalDialogProps: ReturnType<ResultButtonDialogFormAlertFieldsetProps["dialogProps"]> = {
        title: dialogTitle,
        label: dialogLabel,
        labelVariables: {
            representation: value.representation,
        },
        formProps: {
            endpoint: endpoint(value),
            snackbarLabel: snackbarLabel,
            fields: fields,
            onSubmitSuccess: (defaultOnSubmitSuccess) => {
                defaultOnSubmitSuccess();
                reload();
            },
        },
        submitProps: {
            label: actionLabel,
            color: "success",
            variant: "contained",
            endIcon: <PrecisionManufacturingOutlined />,
        },
    };

    return (
        <ResultButtonDialogFormAlertFieldset
            {...{
                result,
                label: actionLabel,
                denyKey: denyKey,
                denyBehavior: "hide",
                color: "success",
                size: "small",
                variant: "contained",
                startIcon: <PrecisionManufacturingOutlined />,
                dialogProps: () =>
                    _.merge(internalDialogProps, typeof dialogProps !== "undefined" ? dialogProps(value) : {}),
                ...props,
            }}
        />
    );
};

export default SelectTemplateVersion;
export { SelectTemplateVersionProps };
