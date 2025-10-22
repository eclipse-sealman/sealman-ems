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
import { Optional } from "@arteneo/forge";
import List, { ListProps } from "~app/components/Crud/List";
import { DeviceEndpointDeviceInterface } from "~app/entities/DeviceEndpointDevice/definitions";
import getColumns from "~app/entities/VpnLog/columns";

interface TableVpnLogsProps extends Optional<ListProps, "endpointPrefix" | "columns"> {
    deviceEndpointDevice: DeviceEndpointDeviceInterface;
}

const TableVpnLogs = ({ deviceEndpointDevice, ...props }: TableVpnLogsProps) => {
    const columns = getColumns(undefined, ["target"]);

    return (
        <List
            {...{
                endpointPrefix: "/vpnlog",
                columns,
                rowsPerPage: 5,
                ...props,
                defaultSorting: {
                    createdAt: "desc",
                    ...(props.defaultSorting ?? {}),
                },
                additionalSorting: {
                    id: "desc",
                },
                additionalFilters: {
                    endpointDevice: {
                        filterBy: "endpointDevice",
                        filterType: "equal",
                        filterValue: deviceEndpointDevice.id,
                    },
                    ...(props.additionalFilters ?? {}),
                },
            }}
        />
    );
};

export default TableVpnLogs;
export { TableVpnLogsProps };
