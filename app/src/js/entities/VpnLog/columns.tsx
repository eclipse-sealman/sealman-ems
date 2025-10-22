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
import { ActionsColumn, getColumns } from "@arteneo/forge";
import LogLevelColumn from "~app/components/Table/columns/LogLevelColumn";
import VpnTargetColumn from "~app/components/Table/columns/VpnTargetColumn";
import TextSqueezedCopyColumn from "~app/components/Table/columns/TextSqueezedCopyColumn";
import ResultDialogMessage from "~app/components/Table/actions/ResultDialogMessage";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";

const columns = {
    logLevel: <LogLevelColumn />,
    target: <VpnTargetColumn />,
    message: <TextSqueezedCopyColumn maxWidth={800} />,
    createdAt: <CreatedAtByColumn />,
    actions: (
        <ActionsColumn>
            <ResultDialogMessage />
        </ActionsColumn>
    ),
};

export default getColumns(columns);
