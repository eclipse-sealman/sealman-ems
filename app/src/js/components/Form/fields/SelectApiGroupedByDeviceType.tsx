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
import { OptionsType, SelectApi, SelectApiProps } from "@arteneo/forge";
import useSelectGroupByDeviceType from "~app/hooks/useSelectGroupByDeviceType";

interface SelectApiGroupedByDeviceTypeProps extends SelectApiProps {
    deviceTypePath?: string;
}

/**
 * SelectApi requires options to include deviceType information (color, icon, representation).
 */
const SelectApiGroupedByDeviceType = ({
    deviceTypePath = "deviceType",
    ...props
}: SelectApiGroupedByDeviceTypeProps) => {
    const { getSelectedOptionLabel, renderOption, renderInput, sortOptions, groupBy } =
        useSelectGroupByDeviceType(deviceTypePath);

    return (
        <SelectApi
            {...{
                processResponse: (response) => {
                    const options: OptionsType = response.data;
                    // Options has to be sorted to avoid duplicate group headers (MUI requirement)
                    return sortOptions(options);
                },
                groupBy,
                disableTranslateGroupBy: true,
                renderInput,
                ...props,
                autocompleteProps: {
                    renderOption,
                    getOptionLabel: getSelectedOptionLabel,
                    ...props.autocompleteProps,
                },
            }}
        />
    );
};

export default SelectApiGroupedByDeviceType;
export { SelectApiGroupedByDeviceTypeProps };
