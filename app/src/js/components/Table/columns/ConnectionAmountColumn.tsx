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
import { useTranslation } from "react-i18next";
import { ColumnPathInterface } from "@arteneo/forge";
import { getIn } from "formik";
import { format } from "date-fns";

interface ConnectionAmountColumnProps extends ColumnPathInterface {
    connectionAmountFromPath: string;
    connectionAggregationPeriod: number;
    connectionAmountPath?: string;
}

const ConnectionAmountColumn = ({
    result,
    columnName,
    path,
    connectionAmountFromPath,
    connectionAggregationPeriod,
    connectionAmountPath,
}: ConnectionAmountColumnProps) => {
    const { t } = useTranslation();
    const utils = useUtils();

    if (typeof columnName === "undefined") {
        throw new Error("ConnectionAmountColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("ConnectionAmountColumn component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;
    if (!value) {
        return null;
    }

    const connectionFrom = getIn(value, connectionAmountFromPath);
    if (typeof connectionFrom === "undefined") {
        return null;
    }

    const connectionFromDate = utils.date(connectionFrom);
    if (connectionFromDate == "Invalid Date") {
        console.warn("ConnectionAmountColumn component: Could not parse date");
        return null;
    }

    const connectionAmount = getIn(value, connectionAmountPath ? connectionAmountPath : columnName);

    return (
        <>
            {t("connectionAmountColumn.amount", {
                count: connectionAmount,
                connectionAmount,
                from: format(connectionFromDate as Date, "dd-MM-yyyy HH:mm:ss"),
                period: connectionAggregationPeriod,
            })}
        </>
    );
};

export default ConnectionAmountColumn;
export { ConnectionAmountColumnProps };
