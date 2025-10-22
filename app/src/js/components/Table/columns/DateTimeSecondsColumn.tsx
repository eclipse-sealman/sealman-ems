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
import { useUtils } from "@mui/x-date-pickers/internals/hooks/useUtils";
import { getIn } from "formik";
import { ColumnPathInterface } from "@arteneo/forge";
import { format } from "date-fns";

const DateTimeSecondsColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("DateTimeSecondsColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("DateTimeSecondsColumn component: Missing required result prop");
    }

    const utils = useUtils();

    const value = getIn(result, path ? path : columnName);
    if (!value) {
        return null;
    }

    const dateValue = utils.date(value);
    if (dateValue == "Invalid Date") {
        console.warn("DateTimeSecondsColumn component: Could not parse date");
        return null;
    }

    return <>{format(dateValue as Date, "dd-MM-yyyy HH:mm:ss")}</>;
};

export default DateTimeSecondsColumn;
export { ColumnPathInterface as DateTimeSecondsColumnProps };
