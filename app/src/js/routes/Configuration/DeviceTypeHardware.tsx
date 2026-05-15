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
import getColumns from "~app/entities/DeviceTypeHardware/columns";
import getFilters from "~app/entities/DeviceTypeHardware/filters";
import getEditFields from "~app/entities/DeviceTypeHardware/editFields";
import Builder from "~app/components/Crud/Builder";
import { useParams } from "react-router-dom";
import { useHandleCatch, useLoader } from "@arteneo/forge";
import { DeviceTypeInterface } from "~app/entities/DeviceType/definitions";
import axios from "axios";
import { FormikValues } from "formik";
import { cloneDeep } from "lodash";

const DeviceTypeHardware = () => {
    const { deviceTypeId } = useParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [deviceType, setDeviceType] = React.useState<undefined | DeviceTypeInterface>(undefined);

    React.useEffect(() => load(), [deviceTypeId]);

    const load = () => {
        if (!deviceTypeId) {
            return;
        }
        showLoader();

        axios
            .get("/options/devicetype/" + deviceTypeId)
            .then((response) => {
                setDeviceType(response.data);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });
    };

    const columns = getColumns();
    const filters = getFilters();
    const fields = getEditFields();

    return (
        <Builder
            {...{
                endpointPrefix: "/devicetypehardware",
                title: "route.title.configuration.deviceTypeHardware",
                listSurfaceTitleProps: {
                    hint: deviceType?.name ? "route.hint.selectedDeviceType" : undefined,
                    hintVariables: deviceType?.name ? { deviceType: deviceType?.name } : {},
                },
                icon: <MemoryOutlined />,
                listProps: {
                    columns,
                    filters,
                    additionalFilters: {
                        deviceType: {
                            filterBy: "deviceType",
                            filterType: "equal",
                            filterValue: deviceTypeId,
                        },
                    },
                    hasCreate: true,
                },
                editProps: {
                    fields: fields,
                    changeSubmitValues: (values: FormikValues) => {
                        const changedValues = cloneDeep(values);
                        delete changedValues.hardwareVersion;

                        return changedValues;
                    },
                },
            }}
        />
    );
};

export default DeviceTypeHardware;
