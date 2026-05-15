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
import { SettingsOutlined } from "@mui/icons-material";
import getColumns from "~app/entities/DeviceTypeCustomDataMapping/columns";
import getFilters from "~app/entities/DeviceTypeCustomDataMapping/filters";
import getFields from "~app/entities/DeviceTypeCustomDataMapping/fields";
import Builder from "~app/components/Crud/Builder";
import { useParams } from "react-router-dom";
import { useHandleCatch, useLoader } from "@arteneo/forge";
import { DeviceTypeInterface } from "~app/entities/DeviceType/definitions";
import axios from "axios";
import { cloneDeep } from "lodash";
import BuilderToolbar from "~app/components/Table/toolbar/BuilderToolbar";
import CopyDefaultCustomDataMappings from "~app/entities/DeviceTypeCustomDataMapping/actions/CopyDefaultCustomDataMappings";

const DeviceTypeCustomDataMapping = () => {
    const { deviceTypeId } = useParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [deviceType, setDeviceType] = React.useState<undefined | DeviceTypeInterface>(undefined);
    const [deviceTypeRefreshCounter, setDeviceTypeRefreshCounter] = React.useState<number>(0);

    React.useEffect(() => load(), [deviceTypeId, deviceTypeRefreshCounter]);

    const load = () => {
        if (!deviceTypeId) {
            return;
        }
        showLoader();

        axios
            .get("/devicetype/" + deviceTypeId)
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
                endpointPrefix: "/devicetypecustomdatamappings",
                title: "route.title.configuration.deviceTypeCustomDataMapping",
                listSurfaceTitleProps: {
                    hint: deviceType?.name ? "route.hint.selectedDeviceType" : undefined,
                    hintVariables: deviceType?.name ? { deviceType: deviceType?.name } : {},
                },
                icon: <SettingsOutlined />,

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
                    hasExportCsv: false,
                    hasExportExcel: false,
                    toolbar: deviceType ? (
                        <BuilderToolbar
                            render={({ createAction }) => (
                                <>
                                    {createAction}
                                    <CopyDefaultCustomDataMappings
                                        {...{
                                            deviceType: deviceType,
                                            onSuccess: () => {
                                                setDeviceTypeRefreshCounter((value) => value + 1);
                                            },
                                        }}
                                    />
                                </>
                            )}
                        />
                    ) : null,
                },
                createProps: {
                    initialValues: {
                        type: "string",
                        variableEnabled: false,
                    },
                    changeSubmitValues: (values) => ({
                        deviceType: deviceTypeId,
                        ...cloneDeep(values),
                        type: values.variableEnabled ? values.type : undefined,
                        variableName:
                            values.variableEnabled && values.variableName ? "data_" + values.variableName : undefined,
                    }),
                    fields,
                },
                editProps: {
                    processInitialValues(fields, initialValues) {
                        return {
                            ...initialValues,
                            variableName: initialValues?.variableName?.startsWith("data_")
                                ? initialValues.variableName.substring(5)
                                : undefined,
                        };
                    },
                    fields,
                    changeSubmitValues: (values) => ({
                        name: values.name,
                        type: values.variableEnabled ? values.type : undefined,
                        path: values.path,
                        variableEnabled: values.variableEnabled,
                        variableName:
                            values.variableEnabled && values.variableName ? "data_" + values.variableName : undefined,
                    }),
                },
            }}
        />
    );
};

export default DeviceTypeCustomDataMapping;
