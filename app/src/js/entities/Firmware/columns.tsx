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
import { getColumns, RepresentationColumn, TextColumn } from "@arteneo/forge";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";
import FirmwareDownload from "~app/entities/Firmware/actions/FirmwareDownload";
import FirmwareShowExternalUrl from "~app/entities/Firmware/actions/FirmwareShowExternalUrl";
import FirmwareNameExtendedColumn from "~app/entities/Firmware/columns/FirmwareNameExtendedColumn";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";
import UpdatedAtByColumn from "~app/components/Table/columns/UpdatedAtByColumn";
import TextSqueezedCopyColumn from "~app/components/Table/columns/TextSqueezedCopyColumn";
import DeviceTypeColumn from "~app/components/Table/columns/DeviceTypeColumn";
import RedirectFirmwareHardwareFiles from "~app/entities/Firmware/actions/RedirectFirmwareHardwareFiles";
import FirmwareShowUpdatePath from "~app/entities/Firmware/actions/FirmwareShowUpdatePath";

const columns = {
    deviceType: <DeviceTypeColumn />,
    name: <FirmwareNameExtendedColumn />,
    version: <TextColumn />,
    requiredFirmware: <RepresentationColumn />,
    filename: <TextColumn />,
    md5: <TextSqueezedCopyColumn />,
    uuid: <TextSqueezedCopyColumn />,
    createdAt: <CreatedAtByColumn />,
    updatedAt: <UpdatedAtByColumn />,
    actions: (
        <BuilderActionsColumn
            {...{
                render: ({ editAction, duplicateAction, deleteAction }) => (
                    <>
                        <RedirectFirmwareHardwareFiles />
                        {editAction}
                        <FirmwareDownload />
                        <FirmwareShowExternalUrl />
                        <FirmwareShowUpdatePath />
                        {duplicateAction}
                        {deleteAction}
                    </>
                ),
            }}
        />
    ),
};

export default getColumns(columns);
