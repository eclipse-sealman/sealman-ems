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
import { formatUptime } from "~app/utilities/format";
import DisplayUnits from "~app/components/Common/DisplayUnits";

interface UptimeColumnProps extends ColumnPathInterface {
    uptimeSecondsPath: string;
    uptimePath?: string;
}

const UptimeColumn = ({ result, columnName, path, uptimeSecondsPath, uptimePath }: UptimeColumnProps) => {
    if (typeof columnName === "undefined") {
        throw new Error("LogLevelColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("LogLevelColumn component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;
    if (!value) {
        return null;
    }

    const uptimeSeconds = getIn(value, uptimeSecondsPath);
    if (typeof uptimeSeconds === "undefined") {
        return <>{getIn(value, uptimePath ? uptimePath : columnName)}</>;
    }

    const units = formatUptime(uptimeSeconds);
    return <DisplayUnits {...{ units }} />;
};

export default UptimeColumn;
export { UptimeColumnProps };
