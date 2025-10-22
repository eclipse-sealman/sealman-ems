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
import axios from "axios";
import { DeviceHubOutlined } from "@mui/icons-material";
import { Form, useHandleCatch, useLoader } from "@arteneo/forge";
import { useNavigate, useParams } from "react-router-dom";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import composeGetFields from "~app/entities/DeviceEndpointDevice/fields";
import { DeviceEndpointDeviceInterface } from "~app/entities/DeviceEndpointDevice/definitions";
import { useUser } from "~app/contexts/User";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";

const DeviceEndpointDeviceEdit = () => {
    const { isAccessGranted } = useUser();
    const navigate = useNavigate();
    const { id } = useParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [deviceEndpointDevice, setDeviceEndpointDevice] = React.useState<undefined | DeviceEndpointDeviceInterface>(
        undefined
    );

    React.useEffect(() => load(), []);

    const load = () => {
        showLoader();

        axios
            .get("/deviceendpointdevice/" + id)
            .then((response) => {
                setDeviceEndpointDevice(response.data);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });
    };

    if (typeof deviceEndpointDevice === "undefined") {
        return null;
    }

    const device = deviceEndpointDevice.device;
    if (typeof device?.virtualSubnetCidr === "undefined") {
        return null;
    }

    if (typeof device?.endpointDevices === "undefined") {
        return null;
    }

    const getFields = composeGetFields(
        device.virtualSubnetCidr,
        device.endpointDevices,
        device.virtualSubnetIpSortable
    );

    const fields = getFields(isAccessGranted({ admin: true, vpnEndpointDevices: true }) ? undefined : ["description"]);

    return (
        <>
            <SurfaceTitle
                {...{
                    title: "route.title.deviceEndpointDevice",
                    subtitle: deviceEndpointDevice.representation,
                    subtitleTo: "/deviceendpointdevice/details/" + deviceEndpointDevice.id,
                    disableSubtitleTranslate: true,
                    hint: "route.hint.edit",
                    icon: <DeviceHubOutlined />,
                }}
            />
            <Surface>
                <Form
                    {...{
                        initialValues: deviceEndpointDevice,
                        endpoint: "/deviceendpointdevice/" + deviceEndpointDevice.id,
                        children: (
                            <CrudFieldset
                                {...{
                                    fields,
                                    backButtonProps: {
                                        // TODO Arek How to handle -1 when this is first page? fallback to details screen
                                        onClick: () => navigate(-1),
                                    },
                                }}
                            />
                        ),
                        onSubmitSuccess: (defaultOnSubmitSuccess) => {
                            defaultOnSubmitSuccess();
                            // TODO Arek How to handle -1 when this is first page? fallback to details screen
                            navigate(-1);
                        },
                        fields,
                    }}
                />
            </Surface>
        </>
    );
};

export default DeviceEndpointDeviceEdit;
