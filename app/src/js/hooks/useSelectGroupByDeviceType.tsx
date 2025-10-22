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
import { Box, InputAdornment } from "@mui/material";
import { OptionInterface, OptionsType, SelectRenderInput, SelectRenderInputProps } from "@arteneo/forge";
import DeviceTypeIconRepresentation, { renderIcon } from "~app/components/Common/DeviceTypeIconRepresentation";
import { getIn } from "formik";

const useSelectGroupByDeviceType = (deviceTypePath = "deviceType") => {
    const getDeviceType = (option: OptionInterface) => getIn(option, deviceTypePath, undefined);

    const getOptionLabel = (option: string | OptionInterface) => {
        if (typeof option === "string") {
            return option;
        }

        return option?.representation;
    };

    const getSelectedOptionLabel = (option: string | OptionInterface) => {
        if (typeof option === "string") {
            return option;
        }

        const deviceType = getDeviceType(option);
        return getOptionLabel(option) + " (" + deviceType?.representation + ")";
    };

    const renderOption = (props: React.HTMLAttributes<HTMLLIElement>, option: OptionInterface) => {
        const deviceType = getDeviceType(option);
        const label = getOptionLabel(option);

        if (!deviceType) {
            return (
                <Box component="li" {...props}>
                    {label}
                </Box>
            );
        }

        return (
            <Box component="li" {...props}>
                <DeviceTypeIconRepresentation
                    icon={deviceType?.icon as string}
                    representation={label}
                    color={deviceType?.color}
                    isAvailable={deviceType?.isAvailable}
                    enabled={deviceType?.enabled}
                />
            </Box>
        );
    };

    const renderInput = (params: SelectRenderInputProps, option?: OptionInterface) => {
        if (option) {
            const deviceType = getDeviceType(option);

            if (deviceType) {
                params.InputProps.startAdornment = (
                    <InputAdornment position="end">
                        {renderIcon(deviceType.icon as string, deviceType.color)}
                    </InputAdornment>
                );
            }
        }

        return <SelectRenderInput {...params} />;
    };

    const groupBy = (option: OptionInterface) => getDeviceType(option)?.representation ?? "";

    // Options has to be sorted to avoid duplicate group headers (MUI requirement)
    const sortOptions = (options: OptionsType) =>
        options.sort((optionA, optionB) => {
            const deviceTypeA = getDeviceType(optionA);
            const deviceTypeB = getDeviceType(optionB);

            if (!deviceTypeA || !deviceTypeB) {
                return 0;
            }

            return deviceTypeA.representation.localeCompare(deviceTypeB.representation);
        });

    return {
        getDeviceType,
        getOptionLabel,
        getSelectedOptionLabel,
        renderOption,
        renderInput,
        sortOptions,
        groupBy,
    };
};

export default useSelectGroupByDeviceType;
