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
import { AXIOS_CANCELLED_UNMOUNTED, useHandleCatch } from "@arteneo/forge";
import getColumns from "~app/entities/Firmware/firmwareUpdatePathColumns";
import { FirmwareInterface } from "~app/entities/Firmware/definitions";
import axios, { AxiosError, AxiosResponse } from "axios";
import { Table, TableBody, TableCell, TableHead, TableRow, TableSortLabel } from "@mui/material";
import { useTranslation } from "react-i18next";

interface TableFirmwareUpdatePathProps {
    firmwareId: number | string;
}

const TableFirmwareUpdatePath = ({ firmwareId }: TableFirmwareUpdatePathProps) => {
    const { t } = useTranslation();
    const handleCatch = useHandleCatch();

    const [firmwareUpdateList, setFirmwareUpdateList] = React.useState<FirmwareInterface[]>([]);

    const columns = getColumns();

    React.useEffect(() => loadFirmwareUpdatePath(), [firmwareId]);

    const loadFirmwareUpdatePath = () => {
        const axiosSource = axios.CancelToken.source();

        axios
            .get("/firmware/update/path/" + firmwareId, { cancelToken: axiosSource.token })
            .then((response: AxiosResponse) => {
                setFirmwareUpdateList(response.data);
            })
            .catch((error: AxiosError) => {
                handleCatch(error);
            });

        return () => {
            axiosSource.cancel(AXIOS_CANCELLED_UNMOUNTED);
        };
    };

    const getHeadTableCell = (columnName: string) => {
        if (columns[columnName]?.props?.disableSorting) {
            return t("label." + columnName);
        }

        return (
            <TableSortLabel
                {...{
                    active: false,
                }}
            >
                {t("label." + columnName)}
            </TableSortLabel>
        );
    };

    return (
        <Table>
            <TableHead>
                <TableRow>
                    {Object.keys(columns).map((columnName) => (
                        <TableCell key={columnName}>{getHeadTableCell(columnName)}</TableCell>
                    ))}
                </TableRow>
            </TableHead>
            <TableBody>
                {firmwareUpdateList.map((result, key) => (
                    <TableRow key={key} hover={true}>
                        {Object.keys(columns).map((columnName) => (
                            <TableCell key={columnName}>
                                {React.cloneElement(columns[columnName], {
                                    result,
                                    columnName: columns[columnName].props?.columnName ?? columnName,
                                })}
                            </TableCell>
                        ))}
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
};

export default TableFirmwareUpdatePath;
export { TableFirmwareUpdatePathProps };
