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
import { BooleanColumn, CollectionRepresentationColumn, EnumColumn, getColumns, TextColumn } from "@arteneo/forge";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";
import DeviceTypeColumn from "~app/components/Table/columns/DeviceTypeColumn";
import TextSqueezedCopyColumn from "~app/components/Table/columns/TextSqueezedCopyColumn";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";
import UpdatedAtByColumn from "~app/components/Table/columns/UpdatedAtByColumn";
import { secretValueBehaviour } from "~app/entities/DeviceTypeSecret/enums";

const columns = {
    deviceType: <DeviceTypeColumn />,
    name: <TextColumn />,
    variableNamePrefix: <TextColumn />,
    description: <TextSqueezedCopyColumn maxWidth={100} />,
    useAsVariable: <BooleanColumn />,
    secretValueBehaviour: <EnumColumn enum={secretValueBehaviour} />,
    secretValueRenewAfterDays: <TextColumn />,
    manualForceRenewal: <BooleanColumn />,
    manualEdit: <BooleanColumn />,
    manualEditRenewReminder: <BooleanColumn />,
    manualEditRenewReminderAfterDays: <BooleanColumn />,
    secretMinimumLength: <TextColumn />,
    secretDigitsAmount: <TextColumn />,
    secretLowercaseLettersAmount: <TextColumn />,
    secretUppercaseLettersAmount: <TextColumn />,
    secretSpecialCharactersAmount: <TextColumn />,
    accessTags: <CollectionRepresentationColumn disableSorting />,
    createdAt: <CreatedAtByColumn />,
    updatedAt: <UpdatedAtByColumn />,

    actions: <BuilderActionsColumn />,
};

export default getColumns(columns);
