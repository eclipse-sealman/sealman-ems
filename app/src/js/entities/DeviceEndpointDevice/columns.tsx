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
import { CollectionRepresentationColumn, getColumns, TextColumn } from "@arteneo/forge";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";
import ResultVpnOpenConnection from "~app/components/Table/actions/ResultVpnOpenConnection";
import VirtualIpColumn from "~app/components/Table/columns/VirtualIpColumn";
import VpnCloseOwnedConnection from "~app/entities/DeviceEndpointDevice/actions/VpnCloseOwnedConnection";

const columns = {
    name: <TextColumn />,
    virtualIp: <VirtualIpColumn {...{ virtualIpHostPartPath: "virtualIpHostPart" }} />,
    physicalIp: <TextColumn />,
    description: <TextColumn />,
    accessTags: <CollectionRepresentationColumn disableSorting />,
    actions: (
        <BuilderActionsColumn
            {...{
                render: ({ detailsAction, editAction, deleteAction }) => (
                    <>
                        {detailsAction}
                        {editAction}
                        <ResultVpnOpenConnection entityPrefix="deviceendpointdevice" />
                        <VpnCloseOwnedConnection />
                        {deleteAction}
                    </>
                ),
            }}
        />
    ),
};

export default getColumns(columns);
