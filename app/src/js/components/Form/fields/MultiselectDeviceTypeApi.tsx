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
import { MultiselectApi, MultiselectApiProps, OptionInterface } from "@arteneo/forge";
import { DeviceTypeOptionInterface } from "~app/entities/DeviceType/definitions";
import { Box } from "@mui/system";
import DeviceTypeIconRepresentation, { renderIcon } from "~app/components/Common/DeviceTypeIconRepresentation";
import { Chip, Checkbox as MuiCheckbox, AutocompleteRenderOptionState } from "@mui/material";
import { CheckBox, CheckBoxOutlineBlank } from "@mui/icons-material";

type MultiselectDeviceTypeApiProps = MultiselectApiProps & {
    getDeviceTypeFromOption?: (option: undefined | OptionInterface) => undefined | DeviceTypeOptionInterface;
    getSelectedOptionLabel?: (option: OptionInterface) => string;
    getOptionLabel?: (option: OptionInterface) => string;
    getGroupBy?: (option: OptionInterface) => string;
};

const MultiselectDeviceTypeApi = ({
    getDeviceTypeFromOption = (option) => option || undefined,
    getSelectedOptionLabel = (option) => option.representation,
    getOptionLabel = (option) => option.representation,
    getGroupBy,
    ...multiselectApiProps
}: MultiselectDeviceTypeApiProps) => {
    if (typeof multiselectApiProps.name === "undefined") {
        throw new Error("Missing name prop. By default it is injected while rendering.");
    }

    const getChosenIcon = (optionSelected: OptionInterface) => {
        return getDeviceTypeFromOption(optionSelected)?.icon;
    };

    const getChosenColor = (optionSelected: OptionInterface) => {
        return getDeviceTypeFromOption(optionSelected)?.color;
    };

    const getChosenIsAvailable = (optionSelected: OptionInterface) => {
        return getDeviceTypeFromOption(optionSelected)?.isAvailable;
    };

    const renderIconOption = (
        props: React.HTMLAttributes<HTMLLIElement>,
        option: OptionInterface,
        { selected }: AutocompleteRenderOptionState
    ) => {
        return (
            <Box component="li" sx={{ "& > img": { mr: 2, flexShrink: 0 } }} {...props}>
                <MuiCheckbox
                    {...{
                        icon: <CheckBoxOutlineBlank {...{ fontSize: "small" }} />,
                        checkedIcon: <CheckBox {...{ fontSize: "small" }} />,
                        style: { marginRight: 8 },
                        checked: selected,
                    }}
                />
                <DeviceTypeIconRepresentation
                    icon={getDeviceTypeFromOption(option)?.icon as string}
                    representation={getOptionLabel(option)}
                    color={getDeviceTypeFromOption(option)?.color}
                    isAvailable={getDeviceTypeFromOption(option)?.isAvailable}
                    enabled={getDeviceTypeFromOption(option)?.enabled}
                />
            </Box>
        );
    };

    let groupByProps = {};
    if (getGroupBy) {
        groupByProps = {
            groupBy: (option: OptionInterface) => getGroupBy(option),
            disableTranslateGroupBy: true,
        };
    }

    return (
        <MultiselectApi
            {...{
                autocompleteProps: {
                    renderTags: (value, getTagProps) =>
                        value.map((option, index) => (
                            <Chip
                                sx={{
                                    opacity: getChosenIsAvailable(option) ? "inherit" : "50%",
                                }}
                                icon={renderIcon(getChosenIcon(option), getChosenColor(option))}
                                label={getOptionLabel(option)}
                                {...getTagProps({ index })}
                                key={index}
                            />
                        )),
                    renderOption: renderIconOption,
                    getOptionLabel: (option: string | OptionInterface) => {
                        if (typeof option === "string") {
                            return option;
                        }
                        return getSelectedOptionLabel(option);
                    },
                },
                ...groupByProps,
                ...multiselectApiProps,
            }}
        />
    );
};

MultiselectDeviceTypeApi.defaultProps = {
    // eslint-disable-next-line
    transformInitialValue: (value: any) => {
        if (Array.isArray(value)) {
            return value.map((valueOption) => {
                // Backend API is serializing it as object
                if (typeof valueOption?.id !== "undefined") {
                    return valueOption.id;
                }

                return valueOption;
            });
        }

        return value;
    },
};

export default MultiselectDeviceTypeApi;
export { MultiselectDeviceTypeApiProps };
