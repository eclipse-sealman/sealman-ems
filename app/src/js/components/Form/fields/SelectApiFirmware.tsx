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
import { OptionInterface, SelectApi, SelectApiProps, SelectRenderInputProps } from "@arteneo/forge";
import { Box } from "@mui/system";
import { InputAdornment, TextField } from "@mui/material";
import FirmwareHardwareFilesIcon from "~app/entities/Firmware/components/FirmwareHardwareFilesIcon";
import { FirmwareOptionsInterface } from "~app/entities/Firmware/definitions";

type SelectApiFirmwareProps = SelectApiProps;

const SelectApiFirmware = (props: SelectApiFirmwareProps) => {
    const renderInput = (params: SelectRenderInputProps, option?: OptionInterface) => {
        const resolvedParams = Object.assign({}, params);

        if (option) {
            resolvedParams.InputProps.startAdornment = (
                <InputAdornment position="end">
                    <FirmwareHardwareFilesIcon {...{ firmware: option as FirmwareOptionsInterface }} />
                </InputAdornment>
            );
        }

        return <TextField {...resolvedParams} />;
    };

    const renderOption = (props: React.HTMLAttributes<HTMLLIElement>, option: OptionInterface) => (
        <Box component="li" {...props}>
            {option.representation}
            <Box sx={{ display: "flex", alignItems: "center", ml: 0.5 }}>
                <FirmwareHardwareFilesIcon {...{ firmware: option as FirmwareOptionsInterface }} />
            </Box>
        </Box>
    );

    return (
        <SelectApi
            {...{
                renderInput,
                ...props,
                autocompleteProps: {
                    renderOption,
                    ...props?.autocompleteProps,
                },
            }}
        />
    );
};

SelectApiFirmware.defaultProps = {
    // eslint-disable-next-line
    transformInitialValue: (value: any) => {
        // Backend API is serializing it as object
        if (typeof value?.id !== "undefined") {
            return value.id;
        }

        return value;
    },
};

export default SelectApiFirmware;
export { SelectApiFirmwareProps };
