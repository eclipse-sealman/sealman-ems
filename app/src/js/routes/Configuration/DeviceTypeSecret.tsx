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
import { VpnKeyOutlined } from "@mui/icons-material";
import getColumns from "~app/entities/DeviceTypeSecret/columns";
import getFilters from "~app/entities/DeviceTypeSecret/filters";
import getFields, { clearSubmitValues } from "~app/entities/DeviceTypeSecret/fields";
import Builder from "~app/components/Crud/Builder";
import { FormikValues } from "formik";
import { useParams } from "react-router-dom";
import { useHandleCatch, useLoader } from "@arteneo/forge";
import { DeviceTypeInterface } from "~app/entities/DeviceType/definitions";
import axios from "axios";

const DeviceTypeSecret = () => {
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
    const fields = getFields();

    return (
        <Builder
            {...{
                endpointPrefix: "/devicetypesecret",
                title: "route.title.configuration.deviceTypeSecret",
                listSurfaceTitleProps: {
                    hint: deviceType?.name ? "route.hint.selectedDeviceType" : undefined,
                    hintVariables: deviceType?.name ? { deviceType: deviceType?.name } : {},
                },
                icon: <VpnKeyOutlined />,
                listProps: {
                    columns,
                    filters,
                    visibleColumnsKey: "devicetypesecret",
                    defaultColumns: [
                        "deviceType",
                        "name",
                        "useAsVariable",
                        "variableNamePrefix",
                        "secretValueBehaviour",
                        "manualEdit",
                        "manualForceRenewal",
                        "description",
                        "accessTags",
                        "updatedAt",
                        "actions",
                    ],
                    additionalFilters: {
                        deviceType: {
                            filterBy: "deviceType",
                            filterType: "equal",
                            filterValue: deviceTypeId,
                        },
                    },
                    hasCreate: true,
                    hasEdit: true,
                    deleteProps: {
                        dialogProps: () => ({
                            label: "deviceTypeSecretDelete.dialog.label",
                        }),
                    },
                },
                editProps: {
                    fields: fields,
                    changeSubmitValues: (values: FormikValues) => {
                        const _values = Object.assign({}, values);
                        delete _values.deviceType;

                        clearSubmitValues(_values);

                        return _values;
                    },
                },
            }}
        />
    );
};

export default DeviceTypeSecret;
