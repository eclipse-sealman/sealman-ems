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
import { TextFilter, TextFilterProps } from "@arteneo/forge";
import { Tooltip } from "@mui/material";
import { HelpOutline } from "@mui/icons-material";
import { useTranslation } from "react-i18next";

type FeatureNameFilterProps = TextFilterProps;

const FeatureNameFilter = (textFilterProps: FeatureNameFilterProps) => {
    const { t } = useTranslation();

    return (
        <TextFilter
            {...{
                fieldProps: {
                    InputProps: {
                        endAdornment: (
                            <Tooltip {...{ title: t("featureNameFilter.tooltip") }}>
                                <HelpOutline />
                            </Tooltip>
                        ),
                    },
                },
                ...textFilterProps,
            }}
        />
    );
};

// * It has to be done via .defaultProps so filterType is passed openly to this component and can be read by Table context
FeatureNameFilter.defaultProps = {
    filterType: "like",
};

export default FeatureNameFilter;
export { FeatureNameFilterProps };
