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

const ImportFileRowLogColumnNameColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("ImportFileRowLogColumnNameColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("ImportFileRowLogColumnNameColumn component: Missing required result prop");
    }

    const { t } = useTranslation();

    const name = getIn(result, path ? path : columnName);
    if (!name) {
        return null;
    }

    const nameParts: string[] = name.replace("[", "").replace("]", "").split(".");
    return <>{nameParts.map((namePart) => (/^\d+$/.test(namePart) ? namePart : t("label." + namePart))).join(" > ")}</>;
};

export default ImportFileRowLogColumnNameColumn;
export { ColumnPathInterface as ImportFileRowLogColumnNameColumnProps };
