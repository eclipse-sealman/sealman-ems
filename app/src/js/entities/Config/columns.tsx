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
import { EnumColumn, getColumns } from "@arteneo/forge";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";
import ConfigShow from "~app/entities/Config/actions/ConfigShow";
import ConfigNameColumn from "~app/entities/Config/columns/ConfigNameColumn";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";
import UpdatedAtByColumn from "~app/components/Table/columns/UpdatedAtByColumn";
import TextSqueezedCopyColumn from "~app/components/Table/columns/TextSqueezedCopyColumn";
import { generator } from "~app/entities/Config/enums";
import DeviceTypeColumn from "~app/components/Table/columns/DeviceTypeColumn";

const columns = {
    deviceType: <DeviceTypeColumn />,
    name: <ConfigNameColumn />,
    generator: <EnumColumn {...{ enum: generator }} />,
    content: <TextSqueezedCopyColumn />,
    createdAt: <CreatedAtByColumn />,
    updatedAt: <UpdatedAtByColumn />,
    actions: (
        <BuilderActionsColumn
            {...{
                render: ({ editAction, duplicateAction, deleteAction }) => (
                    <>
                        {editAction}
                        <ConfigShow />
                        {duplicateAction}
                        {deleteAction}
                    </>
                ),
            }}
        />
    ),
};

export default getColumns(columns);
