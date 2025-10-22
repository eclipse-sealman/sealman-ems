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
import { BooleanColumn, EnumColumn, getColumns, TextColumn } from "@arteneo/forge";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";
import UpdatedAtByColumn from "~app/components/Table/columns/UpdatedAtByColumn";
import { certificateCategory } from "~app/entities/CertificateType/enums";
import { certificateEntity } from "~app/entities/CertificateType/enums";
import IsAvailableColumn from "~app/entities/CertificateType/columns/IsAvailableColumn";

const columns = {
    isAvailable: <IsAvailableColumn disableSorting />,
    enabled: <BooleanColumn />,
    name: <TextColumn />,
    commonNamePrefix: <TextColumn />,
    variablePrefix: <TextColumn />,
    certificateCategory: <EnumColumn {...{ enum: certificateCategory }} />,
    certificateEntity: <EnumColumn {...{ enum: certificateEntity }} />,
    createdAt: <CreatedAtByColumn />,
    updatedAt: <UpdatedAtByColumn />,
    actions: <BuilderActionsColumn />,
};

export default getColumns(columns);
