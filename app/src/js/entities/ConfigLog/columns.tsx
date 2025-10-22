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
import FeatureNameConfigColumn from "~app/components/Table/columns/FeatureNameConfigColumn";
import TextSqueezedCopyColumn from "~app/components/Table/columns/TextSqueezedCopyColumn";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";
import VpnDeviceColumn from "~app/components/Table/columns/VpnDeviceColumn";
import RedirectLogs from "~app/components/Table/actions/RedirectLogs";
import UpdatedAtByColumn from "~app/components/Table/columns/UpdatedAtByColumn";
import ShowConfigLogContent from "~app/entities/ConfigLog/actions/ShowConfigLogContent";

const columns = {
    logLevel: <LogLevelColumn />,
    device: <VpnDeviceColumn />,
    featureName: <FeatureNameConfigColumn disableSorting />,
    md5: <TextSqueezedCopyColumn disableSorting />,
    createdAt: <CreatedAtByColumn />,
    updatedAt: <UpdatedAtByColumn />,
    actions: (
        <ActionsColumn>
            <ShowConfigLogContent />
            <RedirectLogs
                {...{
                    label: "action.communicationLog",
                    to: "/communicationlog/list",
                    filters: (result) => ({
                        id: result?.communicationLog?.id,
                    }),
                }}
            />
        </ActionsColumn>
    ),
};

export default getColumns(columns);
