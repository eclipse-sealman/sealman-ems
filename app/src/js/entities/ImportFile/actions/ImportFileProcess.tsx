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
import { ResultButtonLink, ResultButtonLinkProps, ColumnActionInterface, Optional } from "@arteneo/forge";
import { KeyboardDoubleArrowRightOutlined } from "@mui/icons-material";

type ImportFileProcessProps = Optional<ResultButtonLinkProps, "to"> & ColumnActionInterface;

const ImportFileProcess = ({ result, ...props }: ImportFileProcessProps) => {
    if (typeof result === "undefined") {
        throw new Error("ImportFileProcess component: Missing required result prop");
    }

    if (result.status !== "importing") {
        return null;
    }

    return (
        <ResultButtonLink
            {...{
                label: "importFile.process.action",
                color: "info",
                size: "small",
                variant: "contained",
                startIcon: <KeyboardDoubleArrowRightOutlined />,
                to: "../process/" + result?.id,
                result,
                ...props,
            }}
        />
    );
};

export default ImportFileProcess;
export { ImportFileProcessProps };
