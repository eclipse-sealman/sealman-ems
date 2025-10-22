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
import { PrecisionManufacturingOutlined } from "@mui/icons-material";
import { Optional } from "@arteneo/forge";
import SelectTemplateVersion, {
    SelectTemplateVersionProps,
} from "~app/entities/TemplateVersion/actions/SelectTemplateVersion";

type SelectProductionProps = Optional<
    SelectTemplateVersionProps,
    "dialogProps" | "snackbarLabel" | "dialogTitle" | "dialogLabel" | "endpoint" | "denyKey" | "actionLabel"
>;
const SelectProduction = (props: SelectProductionProps) => {
    return (
        <SelectTemplateVersion
            {...{
                snackbarLabel: "templateVersion.dialog.selectProduction.snackbar.success",
                dialogTitle: "templateVersion.dialog.selectProduction.title",
                dialogLabel: "templateVersion.dialog.selectProduction.label",
                endpoint: (value) => "/templateversion/select/production/" + value.id,
                actionLabel: "templateVersion.action.selectProduction",
                denyKey: "selectProduction",
                startIcon: <PrecisionManufacturingOutlined />,
                ...props,
            }}
        />
    );
};

export default SelectProduction;
export { SelectProductionProps };
