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

import { MemoryOutlined } from "@mui/icons-material";
import React from "react";
import Create, { CreateProps } from "~app/components/Table/toolbar/Create";

type CreateEnabledHardwareFilesProps = CreateProps;

const CreateEnabledHardwareFiles = (props: CreateEnabledHardwareFilesProps) => {
    return (
        <Create
            {...{
                label: "action.createFirmwareEnabledHardwareFiles",
                startIcon: <MemoryOutlined />,
                to: "../create/enabledhardwarefiles/",
                ...props,
            }}
        />
    );
};

export default CreateEnabledHardwareFiles;
export { CreateEnabledHardwareFilesProps };
