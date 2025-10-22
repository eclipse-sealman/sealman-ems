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
import { authenticationMethod, communicationProcedure } from "~app/entities/DeviceType/enums";
import ResultLimitedEdit from "~app/components/Table/actions/ResultLimitedEdit";
import ResultDetails from "~app/components/Table/actions/ResultDetails";
import DeviceTypeColumn from "~app/components/Table/columns/DeviceTypeColumn";
import ResultEdit from "~app/components/Table/actions/ResultEdit";
import ResultDeviceTypeDisable from "~app/components/Table/actions/ResultDeviceTypeDisable";
import ResultDeviceTypeEnable from "~app/components/Table/actions/ResultDeviceTypeEnable";
import RedirectDeviceTypeSecrets from "~app/entities/DeviceType/actions/RedirectDeviceTypeSecrets";

const columns = {
    name: <DeviceTypeColumn {...{ path: "." }} />,
    enabled: <BooleanColumn />,
    deviceName: <TextColumn />,
    certificateCommonNamePrefix: <TextColumn />,
    communicationProcedure: <EnumColumn {...{ enum: communicationProcedure }} />,
    authenticationMethod: <EnumColumn {...{ enum: authenticationMethod }} />,
    routePrefix: <TextColumn />,
    actions: (
        <BuilderActionsColumn
            {...{
                render: ({ duplicateAction, deleteAction }) => (
                    <>
                        <ResultDetails />
                        <ResultLimitedEdit />
                        <ResultEdit denyBehavior="hide" />
                        {duplicateAction}
                        <ResultDeviceTypeDisable />
                        <ResultDeviceTypeEnable />
                        <RedirectDeviceTypeSecrets />
                        {deleteAction}
                    </>
                ),
            }}
        />
    ),
};

export default getColumns(columns);
