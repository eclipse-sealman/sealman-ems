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
import { ActionsColumn, getColumns, RepresentationColumn } from "@arteneo/forge";
import DeviceTypeColumn from "~app/components/Table/columns/DeviceTypeColumn";
import TextSqueezedCopyColumn from "~app/components/Table/columns/TextSqueezedCopyColumn";
import ResultDialogMessage from "~app/components/Table/actions/ResultDialogMessage";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";
import EnumColorColumn from "~app/components/Table/columns/EnumColorColumn";
import { secretOperation } from "~app/entities/SecretLog/enums";
import ResultShowPreviousSecretValue from "~app/entities/SecretLog/actions/ResultShowPreviousSecretValue";
import ResultShowUpdatedSecretValue from "~app/entities/SecretLog/actions/ResultShowUpdatedSecretValue";

const columns = {
    operation: <EnumColorColumn {...{ enum: secretOperation }} />,
    deviceType: <DeviceTypeColumn />,
    device: <RepresentationColumn />,
    deviceTypeSecret: <RepresentationColumn />,
    message: <TextSqueezedCopyColumn maxWidth={580} />,
    createdAt: <CreatedAtByColumn />,
    actions: (
        <ActionsColumn>
            <ResultDialogMessage />
            <ResultShowPreviousSecretValue />
            <ResultShowUpdatedSecretValue />
        </ActionsColumn>
    ),
};

export default getColumns(columns);
