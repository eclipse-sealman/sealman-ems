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
import { TextColumn, getColumns } from "@arteneo/forge";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";
import { type } from "~app/entities/AuditLogChange/enums";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";
import ShowOldValues from "~app/entities/AuditLogChange/actions/ShowOldValues";
import ShowNewValues from "~app/entities/AuditLogChange/actions/ShowNewValues";
import ShowDiffValues from "~app/entities/AuditLogChange/actions/ShowDiffValues";
import EnumColorColumn from "~app/components/Table/columns/EnumColorColumn";

const columns = {
    changeId: <TextColumn path="log.id" disableSorting />,
    type: <EnumColorColumn enum={type} />,
    entityName: <TextColumn />,
    entityId: <TextColumn />,
    createdAt: <CreatedAtByColumn />,
    actions: (
        <BuilderActionsColumn
            {...{
                render: () => (
                    <>
                        <ShowOldValues />
                        <ShowDiffValues />
                        <ShowNewValues />
                    </>
                ),
            }}
        />
    ),
};

export default getColumns(columns);
