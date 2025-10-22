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
import getColumns from "~app/entities/DeviceSecret/columns";
import { useUser } from "~app/contexts/User";

interface TableDeviceSecretsProps extends Optional<ListProps, "endpointPrefix" | "columns"> {
    device: DeviceInterface;
}

const TableDeviceSecrets = ({ device, ...props }: TableDeviceSecretsProps) => {
    const { isAccessGranted } = useUser();

    const columns = getColumns(undefined, !isAccessGranted({ admin: true }) ? ["accessTags"] : undefined);

    return (
        <List
            {...{
                endpointPrefix: "/devicesecret/" + device.id,
                hasEdit: true,
                hasDelete: true,
                columns,
                queryKey: "devicedetailsdevicesecrets",
                ...props,
                editProps: {
                    to: (result) => "/devicesecret/edit/" + result.id,
                    denyBehavior: "hide",
                    ...(props.editProps ?? {}),
                },
                deleteProps: {
                    label: "action.clear",
                    denyKey: "clear",
                    denyBehavior: "hide",
                    dialogProps: (result) => ({
                        title: "resultClearSecretValue.dialog.title",
                        label: "resultClearSecretValue.dialog.label",
                        confirmProps: {
                            label: "action.clear",
                            snackbarLabel: "resultClearSecretValue.snackbar.success",
                            endpoint: {
                                method: "delete",
                                url: "/devicesecret/" + result.id,
                            },
                        },
                    }),
                },
                defaultSorting: {
                    name: "asc",
                    ...(props.defaultSorting ?? {}),
                },
            }}
        />
    );
};

export default TableDeviceSecrets;
export { TableDeviceSecretsProps };
