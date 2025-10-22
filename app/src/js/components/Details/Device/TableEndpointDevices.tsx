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
import getColumns from "~app/entities/DeviceEndpointDevice/columns";
import { useUser } from "~app/contexts/User";

interface TableEndpointDevicesProps extends Optional<ListProps, "endpointPrefix" | "columns"> {
    device: DeviceInterface;
}

const TableEndpointDevices = ({ device, ...props }: TableEndpointDevicesProps) => {
    const { isAccessGranted } = useUser();

    const columns = getColumns(undefined, !isAccessGranted({ admin: true }) ? ["accessTags"] : undefined);

    return (
        <List
            {...{
                endpointPrefix: "/deviceendpointdevice",
                hasDetails: true,
                hasEdit: true,
                hasDelete: true,
                columns,
                queryKey: "devicedetailsdeviceendpointdevices",
                ...props,
                detailsProps: {
                    to: (result) => "/deviceendpointdevice/details/" + result.id,
                    ...(props.detailsProps ?? {}),
                },
                editProps: {
                    to: (result) => "/deviceendpointdevice/edit/" + result.id,
                    ...(props.editProps ?? {}),
                },
                deleteProps: {
                    denyBehavior: "hide",
                },
                defaultSorting: {
                    createdAt: "desc",
                    ...(props.defaultSorting ?? {}),
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

export default TableEndpointDevices;
export { TableEndpointDevicesProps };
