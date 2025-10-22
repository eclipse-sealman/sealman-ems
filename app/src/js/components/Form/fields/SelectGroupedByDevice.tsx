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
import { Select, SelectProps } from "@arteneo/forge";
import useSelectGroupByDevice from "~app/hooks/useSelectGroupByDevice";

interface SelectGroupedByDeviceProps extends SelectProps {
    devicePath?: string;
}

/**
 * SelectApi requires options to include deviceType information (color, icon, representation).
 */
const SelectGroupedByDevice = ({ devicePath = "device", options, ...props }: SelectGroupedByDeviceProps) => {
    const { getSelectedOptionLabel, renderOption, renderInput, sortOptions, groupBy } =
        useSelectGroupByDevice(devicePath);

    // Options has to be sorted to avoid duplicate group headers (MUI requirement)
    const sortedOptions = sortOptions(options);

    return (
        <Select
            {...{
                groupBy,
                disableTranslateGroupBy: true,
                renderInput,
                ...props,
                options: sortedOptions,
                autocompleteProps: {
                    renderOption,
                    getOptionLabel: getSelectedOptionLabel,
                    ...props.autocompleteProps,
                },
            }}
        />
    );
};

export default SelectGroupedByDevice;
export { SelectGroupedByDeviceProps };
