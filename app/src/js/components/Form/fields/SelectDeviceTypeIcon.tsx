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
import { OptionInterface, SelectEnum, SelectEnumProps, SelectRenderInputProps } from "@arteneo/forge";
import { icon } from "~app/entities/DeviceType/enums";
import { useTranslation } from "react-i18next";
import DeviceTypeIconRepresentation, { renderIcon } from "~app/components/Common/DeviceTypeIconRepresentation";
import { Box } from "@mui/system";
import { FormikProps, FormikValues, getIn, useFormikContext } from "formik";
import { InputAdornment, TextField } from "@mui/material";

type SelectDeviceTypeIconProps = Omit<SelectEnumProps, "enum">;

//This form field is design to use only in deviceType forms (where icon, and color values are available)
const SelectDeviceTypeIcon = ({ ...selectProps }: SelectDeviceTypeIconProps) => {
    if (typeof selectProps.name === "undefined") {
        throw new Error("Missing name prop. By default it is injected while rendering.");
    }

    const { t } = useTranslation();
    const { values }: FormikProps<FormikValues> = useFormikContext();

    const color = values?.color ? values.color : "#000000";

    const renderIconOption = (props: React.HTMLAttributes<HTMLLIElement>, option: OptionInterface) => (
        <Box component="li" sx={{ "& > img": { mr: 2, flexShrink: 0 } }} {...props}>
            <DeviceTypeIconRepresentation
                icon={option.id as string}
                representation={t(option.representation)}
                color={color}
            />
        </Box>
    );

    const path = selectProps?.path ? selectProps?.path : selectProps.name;
    const value = getIn(values, path, undefined);

    return (
        <SelectEnum
            {...{
                enum: icon,
                renderInput: (params: SelectRenderInputProps) => {
                    const resolvedParams = Object.assign({}, params);
                    //Condition added to make sure label renders correctly
                    if (value) {
                        resolvedParams.InputProps.startAdornment = (
                            <InputAdornment position="start">{renderIcon(value, color)}</InputAdornment>
                        );
                    }

                    return <TextField {...resolvedParams} />;
                },
                autocompleteProps: {
                    renderOption: renderIconOption,
                },
                ...selectProps,
            }}
        />
    );
};

export default SelectDeviceTypeIcon;
export { SelectDeviceTypeIconProps };
