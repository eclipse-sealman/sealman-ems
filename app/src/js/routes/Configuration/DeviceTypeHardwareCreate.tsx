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
import { Form } from "@arteneo/forge";
import { useNavigate, useParams } from "react-router-dom";
import getCreateFields from "~app/entities/DeviceTypeHardware/createFields";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";
import { cloneDeep } from "lodash";

const DeviceTypeHardwareCreate = () => {
    const navigate = useNavigate();
    const { deviceTypeId } = useParams();

    const fields = getCreateFields();

    const content = (
        <Form
            {...{
                endpoint: "/devicetypehardware/create",
                children: <CrudFieldset {...{ fields }} />,
                changeSubmitValues: (values) => ({
                    deviceType: deviceTypeId,
                    ...cloneDeep(values),
                }),
                onSubmitSuccess: (defaultOnSubmitSuccess) => {
                    defaultOnSubmitSuccess();
                    navigate("/configuration/devicetypehardware/" + deviceTypeId + "/list");
                },
                fields,
            }}
        />
    );

    const titleProps: SurfaceTitleProps = {
        title: "route.title.configuration.deviceTypeHardware",
        titleTo: "/configuration/devicetypehardware/" + deviceTypeId + "/list",
        subtitle: "route.subtitle.create",
        icon: <MemoryOutlined />,
    };

    return (
        <>
            <SurfaceTitle {...titleProps} />
            <Surface>{content}</Surface>
        </>
    );
};

export default DeviceTypeHardwareCreate;
