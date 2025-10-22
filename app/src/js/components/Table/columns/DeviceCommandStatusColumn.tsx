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
import { getIn } from "formik";
import { Optional } from "@arteneo/forge";
import { useTranslation } from "react-i18next";
import { commandStatus } from "~app/entities/DeviceCommand/enums";
import EnumColorColumn, { EnumColorColumnProps } from "~app/components/Table/columns/EnumColorColumn";

interface DeviceCommandStatusColumnProps extends Optional<EnumColorColumnProps, "enum"> {
    resultPath?: string;
}

const DeviceCommandStatusColumn = ({
    result,
    columnName,
    path,
    resultPath,
    ...props
}: DeviceCommandStatusColumnProps) => {
    if (typeof columnName === "undefined") {
        throw new Error("DeviceCommandStatusColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("DeviceCommandStatusColumn component: Missing required result prop");
    }

    const { t } = useTranslation();

    const value = getIn(result, path ? path : columnName);
    if (!value) {
        return null;
    }

    let tooltip: undefined | React.ReactNode = undefined;
    if (value === "error" || value === "critical") {
        const tooltipResult = resultPath ? getIn(result, resultPath) : result;

        tooltip = (
            <>
                {t("label.commandStatusErrorCategory")}:{" "}
                {tooltipResult?.commandStatusErrorCategory ?? t("label.unknown")}
                <br />
                {t("label.commandStatusErrorPid")}: {tooltipResult?.commandStatusErrorPid ?? t("label.unknown")}
                <br />
                {t("label.commandStatusErrorMessage")}: {tooltipResult?.commandStatusErrorMessage ?? t("label.unknown")}
            </>
        );
    }

    return <EnumColorColumn {...{ enum: commandStatus, result, columnName, path, resultPath, tooltip, ...props }} />;
};

export default DeviceCommandStatusColumn;
export { DeviceCommandStatusColumnProps };
