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

const UpdatedAtByColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("UpdatedAtByColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("UpdatedAtByColumn component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;
    const utils = useUtils();

    if (!value) {
        return null;
    }

    let dateValue = null;
    if (value?.updatedAt) {
        dateValue = utils.date(value?.updatedAt);
        if (dateValue == "Invalid Date") {
            console.warn("UpdatedAtByColumn component: Could not parse date");
            dateValue = null;
        }
    }

    return (
        <>
            {dateValue !== null && (
                <Box {...{ sx: { whiteSpace: "nowrap" } }}>{format(dateValue as Date, "dd-MM-yyyy HH:mm:ss")}</Box>
            )}
            {typeof value?.updatedBy?.representation !== "undefined" && (
                <Box {...{ sx: { fontSize: 13 } }}>({value?.updatedBy?.representation})</Box>
            )}
        </>
    );
};

export default UpdatedAtByColumn;
export { ColumnPathInterface as UpdatedAtByColumnProps };
