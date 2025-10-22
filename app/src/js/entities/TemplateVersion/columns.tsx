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
import {
    CollectionRepresentationColumn,
    ColumnsInterface,
    EnumColumn,
    getColumns,
    RepresentationColumn,
    TextColumn,
} from "@arteneo/forge";
import CreatedAtByColumn from "~app/components/Table/columns/CreatedAtByColumn";
import UpdatedAtByColumn from "~app/components/Table/columns/UpdatedAtByColumn";
import { cidr } from "~app/enums/Cidr";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";
import { DeviceConfigurationTypeInterface } from "~app/entities/DeviceType/definitions";
import DetachStaging from "~app/entities/TemplateVersion/actions/DetachStaging";
import DetachProduction from "~app/entities/TemplateVersion/actions/DetachProduction";
import SelectStaging from "~app/entities/TemplateVersion/actions/SelectStaging";
import SelectProduction from "~app/entities/TemplateVersion/actions/SelectProduction";
import TemplateVersionResultDuplicate from "~app/components/Details/Template/TemplateVersionResultDuplicate";
import ConfigShowColumn from "~app/components/Table/columns/ConfigShowColumn";
import MasqueradeTypeColumn from "~app/components/Table/columns/MasqueradeTypeColumn";

const composeGetColumns = (deviceType: DeviceConfigurationTypeInterface, limited: boolean, limitedVpn: boolean) => {
    const columns: ColumnsInterface = {
        name: <TextColumn />,
        description: <TextColumn />,
    };

    if (deviceType.hasConfig1) {
        columns.config1 = <ConfigShowColumn />;
    }

    if (deviceType.hasConfig2) {
        columns.config2 = <ConfigShowColumn />;
    }

    if (deviceType.hasConfig3) {
        columns.config3 = <ConfigShowColumn />;
    }

    if (deviceType.hasFirmware1) {
        columns.firmware1 = <RepresentationColumn />;
    }

    if (deviceType.hasFirmware2) {
        columns.firmware2 = <RepresentationColumn />;
    }

    if (deviceType.hasFirmware3) {
        columns.firmware3 = <RepresentationColumn />;
    }

    columns.accessTags = <CollectionRepresentationColumn />;
    columns.deviceLabels = <CollectionRepresentationColumn />;

    if (!limited && !limitedVpn) {
        columns.deviceDescription = <TextColumn />;
    }

    if (!limited && !limitedVpn && deviceType.isEndpointDevicesAvailable) {
        columns.virtualSubnetCidr = <EnumColumn {...{ enum: cidr }} />;
    }

    if (!limited && !limitedVpn && deviceType.isMasqueradeAvailable) {
        columns.masqueradeType = <MasqueradeTypeColumn />;
    }

    columns.createdAt = <CreatedAtByColumn />;
    columns.updatedAt = <UpdatedAtByColumn />;
    columns.actions = (
        <BuilderActionsColumn
            {...{
                render: ({ editAction, deleteAction }) => (
                    <>
                        <DetachStaging />
                        <DetachProduction />
                        <SelectStaging />
                        <SelectProduction />
                        {editAction}
                        <TemplateVersionResultDuplicate />
                        {deleteAction}
                    </>
                ),
            }}
        />
    );

    return getColumns(columns);
};

export default composeGetColumns;
