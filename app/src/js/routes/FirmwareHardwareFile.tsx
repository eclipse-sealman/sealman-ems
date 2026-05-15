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
import { MemoryOutlined } from "@mui/icons-material";
import getColumns from "~app/entities/FirmwareHardwareFile/columns";
import composeGetFilters from "~app/entities/FirmwareHardwareFile/filters";
import Builder from "~app/components/Crud/Builder";
import { useParams } from "react-router-dom";
import { FirmwareInterface } from "~app/entities/Firmware/definitions";
import useEndpoint from "~app/hooks/useEndpoint";
import { getFeatureName } from "~app/entities/Firmware/utilities";
import { useUser } from "~app/contexts/User";

const FirmwareHardwareFile = () => {
    const { isAccessGranted } = useUser();
    const { firmwareId } = useParams();
    const { object: firmware } = useEndpoint<FirmwareInterface>("/firmware/" + firmwareId);

    const deviceType = firmware?.deviceType;
    if (typeof firmware === "undefined" || typeof deviceType === "undefined") {
        return null;
    }

    const columns = getColumns();
    const getFilters = composeGetFilters(deviceType.id);
    const filters = getFilters(undefined, !isAccessGranted({ admin: true }) ? ["updatedBy", "createdBy"] : undefined);

    const featureName = getFeatureName(deviceType, firmware.feature);

    return (
        <Builder
            {...{
                endpointPrefix: "/firmwarehardwarefile",
                title: "route.title.firmwareHardwareFile",
                listSurfaceTitleProps: {
                    hint: "route.hint.firmwareHardwareFiles",
                    hintVariables: {
                        feature: featureName,
                        firmwareName: firmware.name,
                        deviceType: deviceType.name,
                    },
                },
                icon: <MemoryOutlined />,
                listProps: {
                    columns,
                    filters,
                    additionalFilters: {
                        deviceType: {
                            filterBy: "firmware",
                            filterType: "equal",
                            filterValue: firmwareId,
                        },
                    },
                    hasCreate: true,
                    createProps: {
                        deny: firmware.deny,
                        denyKey: "hardwareFilesCreate",
                    },
                },
            }}
        />
    );
};

export default FirmwareHardwareFile;
