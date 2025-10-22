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
import { getIn } from "formik";
import { formatBytes } from "~app/utilities/format";

const FormatBytesColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("FormatBytesColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("FormatBytesColumn component: Missing required result prop");
    }

    const value = getIn(result, path ? path : columnName);
    if (!value) {
        return null;
    }

    return <>{formatBytes(value)}</>;
};

export default FormatBytesColumn;
export { ColumnPathInterface as FormatBytesColumnProps };
