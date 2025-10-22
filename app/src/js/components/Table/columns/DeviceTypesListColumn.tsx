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
import { ColumnPathInterface, OptionInterface } from "@arteneo/forge";
import DeviceTypeIconRepresentation from "~app/components/Common/DeviceTypeIconRepresentation";
import { Box } from "@mui/material";

const DeviceTypesListColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("DeviceTypesListColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("DeviceTypesListColumn component: Missing required result prop");
    }

    const deviceTypeList = path === "." ? result : getIn(result, path ? path : columnName);

    if (!deviceTypeList) {
        return null;
    }

    return (
        <>
            {deviceTypeList.length > 0 && (
                <Box sx={{ display: "flex", flexWrap: "wrap" }}>
                    {deviceTypeList.map((deviceType: OptionInterface, key: number) => (
                        <Box key={key} sx={{ display: "flex", m: 1 }}>
                            <DeviceTypeIconRepresentation
                                representation={deviceType?.representation}
                                icon={deviceType?.icon}
                                color={deviceType?.color}
                                isAvailable={deviceType?.isAvailable}
                                enabled={deviceType?.enabled}
                            />
                        </Box>
                    ))}
                </Box>
            )}
        </>
    );
};

export default DeviceTypesListColumn;
export { ColumnPathInterface as DeviceTypesListColumnColumnProps };
