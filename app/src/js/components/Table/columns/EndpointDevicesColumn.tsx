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
import { ColumnPathInterface } from "@arteneo/forge";
import { Box, Table, TableBody, TableCell, TableHead, TableRow, Tooltip } from "@mui/material";
import { HelpOutline } from "@mui/icons-material";
import { useTranslation } from "react-i18next";
import { TemplateVersionEndpointDeviceInterface } from "~app/entities/TemplateVersion/definitions";

const EndpointDevicesColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    const { t } = useTranslation();

    if (typeof columnName === "undefined") {
        throw new Error("EndpointDevicesColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("EndpointDevicesColumn component: Missing required result prop");
    }

    const endpointDevices: TemplateVersionEndpointDeviceInterface[] = getIn(result, path ? path : columnName, []);
    if (endpointDevices.length === 0) {
        return null;
    }

    return (
        <Table {...{ size: "small", padding: "none" }}>
            <TableHead>
                <TableRow>
                    <TableCell>{t("label.name")}</TableCell>
                    <TableCell>{t("label.physicalIp")}</TableCell>
                    <TableCell>{t("label.virtualIpHostPart")}</TableCell>
                    <TableCell>{t("label.accessTags")}</TableCell>
                </TableRow>
            </TableHead>
            <TableBody>
                {endpointDevices.map(({ id, name, description, physicalIp, virtualIpHostPart, accessTags }) => (
                    <TableRow key={id}>
                        <TableCell>
                            <Box {...{ sx: { display: "flex", alignItems: "center", gap: 0.5 } }}>
                                <span>{name}</span>
                                {description && (
                                    <Tooltip title={description}>
                                        <HelpOutline fontSize="inherit" />
                                    </Tooltip>
                                )}
                            </Box>
                        </TableCell>
                        <TableCell>{physicalIp}</TableCell>
                        <TableCell>{t("virtualIpHostPart.option", { number: virtualIpHostPart })}</TableCell>
                        <TableCell>{accessTags.map((accessTag) => accessTag.representation).join(", ")}</TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
};

export default EndpointDevicesColumn;
export { ColumnPathInterface as EndpointDevicesColumnProps };
