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
import { useTranslation } from "react-i18next";

const UpdatedAtByLineColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    const { t } = useTranslation();

    if (typeof columnName === "undefined") {
        throw new Error("UpdatedAtByLineColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("UpdatedAtByLineColumn component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;
    const utils = useUtils();

    if (!value) {
        return null;
    }

    let formattedDate = null;
    if (value?.updatedAt) {
        let dateValue = utils.date(value?.updatedAt);
        if (dateValue == "Invalid Date") {
            console.warn("UpdatedAtByLineColumn component: Could not parse date");
            dateValue = null;
        }

        if (dateValue !== null) {
            formattedDate = format(dateValue as Date, "dd-MM-yyyy HH:mm:ss");
        }
    }

    if (!formattedDate && !value?.updatedBy) {
        return null;
    }

    let label = "updatedAtByLineColumn.both";
    if (!formattedDate) {
        label = "updatedAtByLineColumn.onlyUpdatedBy";
    }
    if (!value?.updatedBy) {
        label = "updatedAtByLineColumn.onlyUpdatedAt";
    }

    return <>{t(label, { formattedUpdatedAt: formattedDate, updatedBy: value?.updatedBy?.representation })}</>;
};

export default UpdatedAtByLineColumn;
export { ColumnPathInterface as UpdatedAtByLineColumnProps };
