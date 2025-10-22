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
import { ResultEdit as ForgeResultEdit, ResultEditProps } from "@arteneo/forge";
import { EditOutlined } from "@mui/icons-material";

const ResultEdit = (props: ResultEditProps) => {
    return (
        <ForgeResultEdit
            {...{
                color: "info",
                size: "small",
                startIcon: <EditOutlined />,
                deny: props?.result?.deny,
                ...props,
            }}
        />
    );
};

export default ResultEdit;
export { ResultEditProps };
