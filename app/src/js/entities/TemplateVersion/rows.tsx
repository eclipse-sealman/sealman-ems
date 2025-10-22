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
import { EnumColumn, RepresentationColumn, CollectionRepresentationColumn, TextColumn } from "@arteneo/forge";
import { getRows } from "~app/utilities/common";
import { DisplayRowsInterface } from "~app/components/Display/Display";
import { DeviceConfigurationTypeInterface } from "~app/entities/DeviceType/definitions";
import { cidr } from "~app/enums/Cidr";
import { TemplateVersionInterface } from "~app/entities/TemplateVersion/definitions";
import { DisplayRowTitleProps } from "~app/components/Display/DisplayRowTitle";
import CreatedAtByLineColumn from "~app/components/Table/columns/CreatedAtByLineColumn";
import UpdatedAtByLineColumn from "~app/components/Table/columns/UpdatedAtByLineColumn";
import MasqueradeTypeColumn from "~app/components/Table/columns/MasqueradeTypeColumn";
import VariablesColumn from "~app/components/Table/columns/VariablesColumn";
import EndpointDevicesColumn from "~app/components/Table/columns/EndpointDevicesColumn";
import ConfigShowEditColumn from "~app/components/Table/columns/ConfigShowEditColumn";

const composeGetTitleProps = (templateVersion: TemplateVersionInterface) => {
    return (rowKey: string): DisplayRowTitleProps => {
        switch (rowKey) {
            case "config1":
                return {
                    title: "label.config",
                    titleVariables: {
                        configName: templateVersion.deviceType.nameConfig1,
                    },
                };
            case "config2":
                return {
                    title: "label.config",
                    titleVariables: {
                        configName: templateVersion.deviceType.nameConfig2,
                    },
                };
            case "config3":
                return {
                    title: "label.config",
                    titleVariables: {
                        configName: templateVersion.deviceType.nameConfig3,
                    },
                };
            case "firmware1":
                return {
                    title: "label.firmware",
                    titleVariables: {
                        firmwareName: templateVersion.deviceType.nameFirmware1,
                    },
                };
            case "firmware2":
                return {
                    title: "label.firmware",
                    titleVariables: {
                        firmwareName: templateVersion.deviceType.nameFirmware2,
                    },
                };
            case "firmware3":
                return {
                    title: "label.firmware",
                    titleVariables: {
                        firmwareName: templateVersion.deviceType.nameFirmware3,
                    },
                };
        }

        return {
            title: "label." + rowKey,
        };
    };
};

const composeGetRows = (deviceType: DeviceConfigurationTypeInterface, limited: boolean, limitedVpn: boolean) => {
    const rows: DisplayRowsInterface = {
        description: <TextColumn />,
    };

    if (!limited && !limitedVpn) {
        rows.deviceDescription = <TextColumn />;
    }

    rows.deviceLabels = <CollectionRepresentationColumn />;

    if (deviceType.hasConfig1) {
        rows.config1 = <ConfigShowEditColumn />;
    }

    if (deviceType.hasConfig2) {
        rows.config2 = <ConfigShowEditColumn />;
    }

    if (deviceType.hasConfig3) {
        rows.config3 = <ConfigShowEditColumn />;
    }

    if (deviceType.hasFirmware1) {
        rows.firmware1 = <RepresentationColumn />;
    }

    if (deviceType.hasFirmware2) {
        rows.firmware2 = <RepresentationColumn />;
    }

    if (deviceType.hasFirmware3) {
        rows.firmware3 = <RepresentationColumn />;
    }

    if (deviceType.hasVariables) {
        rows.variables = <VariablesColumn />;
    }

    if (!limited && !limitedVpn && deviceType.isMasqueradeAvailable) {
        rows.masqueradeType = <MasqueradeTypeColumn />;
    }

    if (!limited && !limitedVpn && deviceType.isEndpointDevicesAvailable) {
        rows.virtualSubnetCidr = <EnumColumn {...{ enum: cidr }} />;
    }

    if (!limited && !limitedVpn && deviceType.isEndpointDevicesAvailable) {
        rows.endpointDevices = <EndpointDevicesColumn />;
    }

    rows.accessTags = <CollectionRepresentationColumn />;
    rows.updatedAtBy = <UpdatedAtByLineColumn />;
    rows.createdAtBy = <CreatedAtByLineColumn />;

    return getRows(rows);
};

export default composeGetRows;
export { composeGetTitleProps };
