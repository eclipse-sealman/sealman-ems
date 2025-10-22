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
import { DeviceInterface } from "~app/entities/Device/definitions";
import getColumns from "~app/entities/DeviceCommand/columns";

interface TableDeviceCommandsProps extends Optional<ListProps, "endpointPrefix" | "columns"> {
    device: DeviceInterface;
}

const TableDeviceCommands = ({ device, ...props }: TableDeviceCommandsProps) => {
    const columns = getColumns(["commandStatus", "commandName", "commandTransactionId", "expireAt", "createdAt"]);

    return (
        <List
            {...{
                endpointPrefix: "/devicecommand",
                columns,
                rowsPerPage: 5,
                queryKey: "devicedetailsdevicecommands",
                ...props,
                defaultSorting: {
                    createdAt: "desc",
                    ...(props.defaultSorting ?? {}),
                },
                additionalSorting: {
                    id: "desc",
                },
                additionalFilters: {
                    device: {
                        filterBy: "device",
                        filterType: "equal",
                        filterValue: device.id,
                    },
                    ...(props.additionalFilters ?? {}),
                },
            }}
        />
    );
};

export default TableDeviceCommands;
export { TableDeviceCommandsProps };
