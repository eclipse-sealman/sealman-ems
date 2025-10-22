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
import { ColumnPathInterface } from "@arteneo/forge";
import { useUtils } from "@mui/x-date-pickers/internals/hooks/useUtils";
import { getIn } from "formik";
import { format } from "date-fns";
import { Box } from "@mui/material";

const CreatedAtByColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("CreatedAtByColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("CreatedAtByColumn component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;
    const utils = useUtils();

    if (!value) {
        return null;
    }

    let dateValue = null;
    if (value?.createdAt) {
        dateValue = utils.date(value?.createdAt);
        if (dateValue == "Invalid Date") {
            console.warn("CreatedAtByColumn component: Could not parse date");
            dateValue = null;
        }
    }

    return (
        <>
            {dateValue !== null && (
                <Box {...{ sx: { whiteSpace: "nowrap" } }}>{format(dateValue as Date, "dd-MM-yyyy HH:mm:ss")}</Box>
            )}
            {typeof value?.createdBy?.representation !== "undefined" && (
                <Box {...{ sx: { fontSize: 13 } }}>({value?.createdBy?.representation})</Box>
            )}
        </>
    );
};

export default CreatedAtByColumn;
export { ColumnPathInterface as CreatedAtByColumnProps };
