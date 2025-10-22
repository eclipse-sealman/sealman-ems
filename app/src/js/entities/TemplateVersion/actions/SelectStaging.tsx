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
import { ScienceOutlined } from "@mui/icons-material";
import { Optional } from "@arteneo/forge";
import SelectTemplateVersion, {
    SelectTemplateVersionProps,
} from "~app/entities/TemplateVersion/actions/SelectTemplateVersion";

type SelectStagingProps = Optional<
    SelectTemplateVersionProps,
    "dialogProps" | "snackbarLabel" | "dialogTitle" | "dialogLabel" | "endpoint" | "denyKey" | "actionLabel"
>;

const SelectStaging = (props: SelectStagingProps) => {
    return (
        <SelectTemplateVersion
            {...{
                snackbarLabel: "templateVersion.dialog.selectStaging.snackbar.success",
                dialogTitle: "templateVersion.dialog.selectStaging.title",
                dialogLabel: "templateVersion.dialog.selectStaging.label",
                endpoint: (value) => "/templateversion/select/staging/" + value.id,
                actionLabel: "templateVersion.action.selectStaging",
                denyKey: "selectStaging",
                startIcon: <ScienceOutlined />,
                ...props,
            }}
        />
    );
};

export default SelectStaging;
export { SelectStagingProps };
