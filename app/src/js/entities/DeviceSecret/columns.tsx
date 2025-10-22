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
import { getColumns, TextColumn, TextTruncateTooltipColumn } from "@arteneo/forge";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";
import UpdatedAtByColumn from "~app/components/Table/columns/UpdatedAtByColumn";
import ResultCreateSecret from "~app/entities/DeviceSecret/actions/ResultCreateSecret";
import ResultDialogShowDeviceSecret from "~app/entities/DeviceSecret/actions/ResultDialogShowDeviceSecret";
import RenewedAtColumn from "~app/entities/DeviceSecret/columns/RenewedAtColumn";
import ResultDialogShowVariablesDeviceSecret from "~app/entities/DeviceSecret/actions/ResultDialogShowVariablesDeviceSecret";
import ResultEnableForceRenewalSecret from "~app/entities/DeviceSecret/actions/ResultEnableForceRenewalSecret";
import ResultDisableForceRenewalSecret from "~app/entities/DeviceSecret/actions/ResultDisableForceRenewalSecret";

const columns = {
    name: <TextColumn path="deviceTypeSecret.name" />,
    description: <TextTruncateTooltipColumn path="deviceTypeSecret.description" />,
    renewedAt: <RenewedAtColumn />,
    updatedAt: <UpdatedAtByColumn />,
    actions: (
        <BuilderActionsColumn
            {...{
                render: ({ editAction, deleteAction }) => (
                    <>
                        <ResultDialogShowDeviceSecret />
                        <ResultDialogShowVariablesDeviceSecret />
                        <ResultCreateSecret />
                        <ResultEnableForceRenewalSecret />
                        <ResultDisableForceRenewalSecret />
                        {editAction}
                        {deleteAction}
                    </>
                ),
            }}
        />
    ),
};

export default getColumns(columns);
