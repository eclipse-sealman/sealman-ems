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
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import getEditSourceExternalUrlFields from "~app/entities/FirmwareHardwareFile/editSourceExternalUrlFields";
import { getFeatureName } from "~app/entities/Firmware/utilities";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";
import useEndpoint from "~app/hooks/useEndpoint";
import { FirmwareHardwareFileInterface } from "~app/entities/FirmwareHardwareFile/definitions";
import { FirmwareInterface } from "~app/entities/Firmware/definitions";

const FirmwareHardwareFileEditExternalUrl = () => {
    const navigate = useNavigate();
    const { id } = useParams();

    const { object: firmwareHardwareFile } = useEndpoint<FirmwareHardwareFileInterface>("/firmwarehardwarefile/" + id);

    if (typeof firmwareHardwareFile === "undefined") {
        return null;
    }

    const fields = getEditSourceExternalUrlFields();

    // Casting to FirmwareInterface in not entirely correct, but lets leave it for simplicity sake
    const firmware = firmwareHardwareFile.firmware as FirmwareInterface;
    const firmwareId = firmware.id;
    const deviceType = firmware.deviceType;

    return (
        <>
            <SurfaceTitle
                {...{
                    title: "route.title.firmwareHardwareFile",
                    titleTo: "/firmwarehardwarefile/" + firmwareId + "/list",
                    subtitle: "route.subtitle.editRepresentationDeviceTypeAndFeature",
                    subtitleVariables: {
                        representation: firmware.representation,
                        feature: getFeatureName(deviceType, firmware.feature),
                        deviceType: deviceType.name,
                    },
                    hint: "route.hint.editRepresentation",
                    hintVariables: {
                        representation: firmwareHardwareFile.representation,
                    },
                    icon: <MemoryOutlined />,
                }}
            />
            <Surface>
                <Form
                    {...{
                        initialValues: firmwareHardwareFile,
                        endpoint: "/firmwarehardwarefile/" + id + "/source/externalurl/edit",
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

export default FirmwareHardwareFileEditExternalUrl;
