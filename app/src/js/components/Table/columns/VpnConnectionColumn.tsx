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
import { format } from "date-fns";
import { Box } from "@mui/material";
import { useTranslation } from "react-i18next";

const VpnConnectionColumn = ({ result, columnName }: ColumnPathInterface) => {
    const { t } = useTranslation();

    if (typeof columnName === "undefined") {
        throw new Error("VpnConnectionColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("VpnConnectionColumn component: Missing required result prop");
    }

    const utils = useUtils();

    let connectionStartAt = undefined;
    if (result?.connectionStartAt) {
        connectionStartAt = utils.date(result?.connectionStartAt);
        if (connectionStartAt == "Invalid Date") {
            console.warn("VpnConnectionColumn component: Could not parse date");
            connectionStartAt = undefined;
        }
    }

    let connectionEndAt = undefined;
    if (result?.connectionEndAt) {
        connectionEndAt = utils.date(result?.connectionEndAt);
        if (connectionEndAt == "Invalid Date") {
            console.warn("VpnConnectionColumn component: Could not parse date");
            connectionEndAt = undefined;
        }
    }

    return (
        <>
            {connectionStartAt !== undefined && (
                <Box {...{ sx: { whiteSpace: "nowrap" } }}>
                    {t("label.connectedSince")}&nbsp;
                    {format(connectionStartAt as Date, "dd-MM-yyyy HH:mm:ss")}
                </Box>
            )}
            {typeof connectionEndAt !== "undefined" && (
                <Box {...{ sx: { fontSize: 13 } }}>
                    ({t("label.validTo")}&nbsp;{format(connectionEndAt as Date, "dd-MM-yyyy HH:mm:ss")})
                </Box>
            )}
        </>
    );
};

export default VpnConnectionColumn;
export { ColumnPathInterface as VpnConnectionColumnProps };
