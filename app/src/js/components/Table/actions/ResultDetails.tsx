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
import { VisibilityOutlined } from "@mui/icons-material";

type ResultDetailsProps = Optional<ResultButtonLinkProps, "to"> & ColumnActionInterface;

const ResultDetails = ({ result, ...props }: ResultDetailsProps) => {
    if (typeof result === "undefined") {
        throw new Error("ResultDetails component: Missing required result prop");
    }

    return (
        <ResultButtonLink
            {...{
                label: "action.details",
                color: "success",
                size: "small",
                variant: "contained",
                startIcon: <VisibilityOutlined />,
                to: "../details/" + result?.id,
                denyKey: "details",
                result,
                deny: result?.deny,
                ...props,
            }}
        />
    );
};

export default ResultDetails;
export { ResultDetailsProps };
