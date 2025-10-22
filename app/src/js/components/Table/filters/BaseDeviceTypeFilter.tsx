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
import { InputAdornment, TextField } from "@mui/material";
import { OptionInterface, SelectApiFilter, SelectApiFilterProps, SelectRenderInputProps } from "@arteneo/forge";
import DeviceTypeIconRepresentation, { renderIcon } from "~app/components/Common/DeviceTypeIconRepresentation";
import { Box } from "@mui/system";
import { FormikProps, FormikValues, getIn, useFormikContext } from "formik";
import { AxiosResponse } from "axios";
import { DeviceTypeOptionInterface } from "~app/entities/DeviceType/definitions";

interface BaseDeviceTypeFilterProps extends SelectApiFilterProps {
    getDeviceTypeFromOption?: (option: undefined | OptionInterface) => undefined | DeviceTypeOptionInterface;
    getSelectedOptionLabel?: (option: OptionInterface) => string;
    getOptionLabel?: (option: OptionInterface) => string;
    getGroupBy?: (option: OptionInterface) => string;
}

//This filter field is designed to use only with device options where color and icon are available (additional identification group added to DeviceType entity)
const BaseDeviceTypeFilter = ({
    getDeviceTypeFromOption = (option) => option || undefined,
    getSelectedOptionLabel = (option) => option.representation,
    getOptionLabel = (option) => option.representation,
    getGroupBy,
    ...selectApiFilter
}: BaseDeviceTypeFilterProps) => {
    if (typeof selectApiFilter.name === "undefined") {
        throw new Error("Missing name prop. By default it is injected while rendering.");
    }

    const { values }: FormikProps<FormikValues> = useFormikContext();
    const [options, setOptions] = React.useState<OptionInterface[]>([]);

    const saveOptions = (response: AxiosResponse) => {
        setOptions(response.data);
        return response.data;
    };

    const path = selectApiFilter?.path ? selectApiFilter?.path : selectApiFilter.name;
    const value = getIn(values, path, undefined);

    const getChosenIcon = () => {
        if (typeof value !== "undefined") {
            const optionSelected = options.find((option) => {
                return option.id == value;
            });
            return getDeviceTypeFromOption(optionSelected)?.icon;
        }
        return undefined;
    };

    const getChosenColor = () => {
        if (typeof value !== "undefined") {
            const optionSelected = options.find((option) => {
                return option.id == value;
            });
            return getDeviceTypeFromOption(optionSelected)?.color;
        }
        return undefined;
    };

    const getChosenIsAvailable = () => {
        if (typeof value !== "undefined") {
            const optionSelected = options.find((option) => {
                return option.id == value;
            });
            return getDeviceTypeFromOption(optionSelected)?.isAvailable;
        }
        return true;
    };

    const renderIconOption = (props: React.HTMLAttributes<HTMLLIElement>, option: OptionInterface) => (
        <Box component="li" sx={{ "& > img": { mr: 2, flexShrink: 0 } }} {...props}>
            <DeviceTypeIconRepresentation
                icon={getDeviceTypeFromOption(option)?.icon as string}
                representation={getOptionLabel(option)}
                color={getDeviceTypeFromOption(option)?.color}
                isAvailable={getDeviceTypeFromOption(option)?.isAvailable}
                enabled={getDeviceTypeFromOption(option)?.enabled}
            />
        </Box>
    );

    let groupByProps = {};
    if (getGroupBy) {
        groupByProps = {
            groupBy: (option: OptionInterface) => getGroupBy(option),
            disableTranslateGroupBy: true,
        };
    }
    return (
        <SelectApiFilter
            {...{
                processResponse: saveOptions,
                renderInput: (params: SelectRenderInputProps) => {
                    const resolvedParams = Object.assign({}, params);
                    //Condition added to make sure label renders correctly
                    if (getChosenIcon()) {
                        resolvedParams.InputProps.startAdornment = (
                            <InputAdornment position="start">
                                {renderIcon(getChosenIcon(), getChosenColor())}
                            </InputAdornment>
                        );
                    }

                    return (
                        <TextField sx={{ opacity: getChosenIsAvailable() ? "inherit" : "50%" }} {...resolvedParams} />
                    );
                },
                autocompleteProps: {
                    renderOption: renderIconOption,
                    getOptionLabel: (option: string | OptionInterface) => {
                        if (typeof option === "string") {
                            return option;
                        }
                        return getSelectedOptionLabel(option);
                    },
                },
                ...groupByProps,
                ...selectApiFilter,
            }}
        />
    );
};

// * It has to be done via .defaultProps so filterType is passed openly to this component and can be read by Table context
BaseDeviceTypeFilter.defaultProps = {
    filterType: "equal",
};

export default BaseDeviceTypeFilter;
