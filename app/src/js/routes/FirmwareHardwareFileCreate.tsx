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
import composeGetFields from "~app/entities/FirmwareHardwareFile/fields";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";
import { FirmwareInterface } from "~app/entities/Firmware/definitions";
import useEndpoint from "~app/hooks/useEndpoint";
import { getFeatureName } from "~app/entities/Firmware/utilities";

const FirmwareHardwareFileCreate = () => {
    const navigate = useNavigate();
    const { firmwareId } = useParams();

    const { object: firmware } = useEndpoint<FirmwareInterface>("/firmware/" + firmwareId);
    const deviceType = firmware?.deviceType;
    if (typeof firmware === "undefined" || typeof deviceType === "undefined") {
        return null;
    }

    const getFields = composeGetFields(deviceType.id);
    const fields = getFields();

    const featureName = getFeatureName(deviceType, firmware.feature);
    const titleProps: SurfaceTitleProps = {
        title: "route.title.firmwareHardwareFile",
        titleTo: "/firmwarehardwarefile/" + firmwareId + "/list",
        subtitle: "route.subtitle.create",
        hint: "route.hint.firmwareHardwareFiles",
        hintVariables: {
            feature: featureName,
            firmwareName: firmware.name,
            deviceType: deviceType.name,
        },
        icon: <MemoryOutlined />,
    };

    return (
        <>
            <SurfaceTitle {...titleProps} />
            <Surface>
                <Form
                    {...{
                        endpoint: "/firmwarehardwarefile/" + firmwareId + "/create",
                        children: <CrudFieldset {...{ fields }} />,
                        onSubmitSuccess: (defaultOnSubmitSuccess) => {
                            defaultOnSubmitSuccess();
                            navigate("/firmwarehardwarefile/" + firmwareId + "/list");
                        },
                        fields,
                    }}
                />
            </Surface>
        </>
    );
};

export default FirmwareHardwareFileCreate;
