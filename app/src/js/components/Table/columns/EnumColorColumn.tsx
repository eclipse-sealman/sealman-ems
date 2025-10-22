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
import { ColumnPathInterface } from "@arteneo/forge";
import { Chip, ChipProps, Tooltip } from "@mui/material";
import EnumColor from "~app/classes/EnumColor";
import { useTranslation } from "react-i18next";
import { HelpOutlineOutlined } from "@mui/icons-material";

interface EnumColorColumnProps extends ColumnPathInterface {
    enum: EnumColor;
    chipProps?: ChipProps;
    tooltip?: React.ReactNode;
}

const EnumColorColumn = ({ enum: enumClass, chipProps, result, columnName, path, tooltip }: EnumColorColumnProps) => {
    if (typeof columnName === "undefined") {
        throw new Error("EnumChipColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("EnumChipColumn component: Missing required result prop");
    }

    const { t } = useTranslation();

    const value = getIn(result, path ? path : columnName);
    if (!value) {
        return null;
    }

    const chip = (
        <Chip
            {...{
                label: t(enumClass.getLabel(value)),
                icon: typeof tooltip !== "undefined" ? <HelpOutlineOutlined /> : undefined,
                color: enumClass.getColor(value),
                size: "small",
                ...chipProps,
            }}
        />
    );

    if (typeof tooltip !== "undefined") {
        return (
            <Tooltip placement="top" title={tooltip}>
                {chip}
            </Tooltip>
        );
    }

    return chip;
};

export default EnumColorColumn;
export { EnumColorColumnProps };
