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
import useSelectGroupByDeviceType from "~app/hooks/useSelectGroupByDeviceType";

interface SelectGroupedByDeviceTypeProps extends SelectProps {
    deviceTypePath?: string;
}

/**
 * Select requires options to include deviceType information (color, icon, representation).
 */
const SelectGroupedByDeviceType = ({
    deviceTypePath = "deviceType",
    options,
    ...props
}: SelectGroupedByDeviceTypeProps) => {
    const { getSelectedOptionLabel, renderOption, renderInput, sortOptions, groupBy } =
        useSelectGroupByDeviceType(deviceTypePath);

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

export default SelectGroupedByDeviceType;
export { SelectGroupedByDeviceTypeProps };
