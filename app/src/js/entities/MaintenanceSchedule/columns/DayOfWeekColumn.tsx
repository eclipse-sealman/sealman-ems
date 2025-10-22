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
import { useTranslation } from "react-i18next";
import { getIn } from "formik";
import { dayOfWeekOptions } from "~app/entities/MaintenanceSchedule/fields";

const DayOfWeekColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("DayOfWeekColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("DayOfWeekColumn component: Missing required result prop");
    }

    const { t } = useTranslation();
    const value = getIn(result, path ? path : columnName);

    const option = dayOfWeekOptions.find((option) => option.id === value);
    if (typeof option === "undefined") {
        return null;
    }

    return <>{t(option.representation)}</>;
};

export default DayOfWeekColumn;
export { ColumnPathInterface as DayOfWeekColumnProps };
