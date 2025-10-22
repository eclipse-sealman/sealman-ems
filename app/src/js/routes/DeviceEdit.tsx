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
import { Box } from "@mui/material";
import { RouterOutlined } from "@mui/icons-material";
import { Form, useHandleCatch, useLoader } from "@arteneo/forge";
import { useNavigate, useParams } from "react-router-dom";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import composeGetFields from "~app/entities/Device/fields";
import { DeviceInterface } from "~app/entities/Device/definitions";
import { useUser } from "~app/contexts/User";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";
import { changeSubmitValuesCertificateAutomaticBehaviorCollection } from "~app/components/Form/fields/CertificateAutomaticBehaviorCollection";
import { VirtualIpHostPartProps } from "~app/components/Form/fields/VirtualIpHostPart";

const DeviceEdit = () => {
    const { isAccessGranted } = useUser();
    const navigate = useNavigate();
    const { id } = useParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [device, setDevice] = React.useState<undefined | DeviceInterface>(undefined);

    React.useEffect(() => load(), []);

    const load = () => {
        showLoader();

        axios
            .get("/device/" + id)
            .then((response) => {
                setDevice(response.data);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });
    };

    if (typeof device === "undefined") {
        return null;
    }

    let getVirtualSubnetCidr: VirtualIpHostPartProps["getVirtualSubnetCidr"] = undefined;
    if (isAccessGranted({ vpnEndpointDevices: true })) {
        // ROLE_VPN_ENDPOINTDEVICES cannot edit virtualSubnetCidr, so we need to provide it beforehand.
        getVirtualSubnetCidr = () => device.virtualSubnetCidr ?? 32;
    }

    const getFields = composeGetFields(
        device.deviceType,
        isAccessGranted({ smartems: true }),
        device.virtualSubnetIpSortable,
        getVirtualSubnetCidr,
        isAccessGranted({ vpnEndpointDevices: true })
    );

    const adminVpnFieldNames = ["virtualSubnetCidr", "masqueradeType", "masquerades"];

    const vpnEndpointDevicesFields = ["endpointDevices"];

    const adminScepFieldNames = ["useableCertificates"];

    const smartemsFieldNames = [
        "template",
        "name",
        "serialNumber",
        "imsi",
        "imei",
        "model",
        "registrationId",
        "endorsementKey",
        "hardwareVersion",
        "enabled",
        "staging",
        "reinstallFirmware1",
        "reinstallFirmware2",
        "reinstallFirmware3",
        "reinstallConfig1",
        "reinstallConfig2",
        "reinstallConfig3",
        "requestDiagnoseData",
        "requestConfigData",
        "accessTags",
        "variables",
    ];

    const fields = getFields(undefined, [
        ...(isAccessGranted({ adminVpn: true }) ? [] : adminVpnFieldNames),
        ...(isAccessGranted({ adminVpn: true, vpnEndpointDevices: true }) ? [] : vpnEndpointDevicesFields),
        ...(isAccessGranted({ adminScep: true }) ? [] : adminScepFieldNames),
        ...(isAccessGranted({ admin: true, smartems: true }) ? [] : smartemsFieldNames),
    ]);

    return (
        <>
            <SurfaceTitle
                {...{
                    title: "route.title.device",
                    titleTo: "/device/list",
                    subtitle: "route.subtitle.detailsRepresentationDeviceType",
                    subtitleVariables: {
                        representation: device.representation,
                        deviceType: device.deviceType?.name,
                    },
                    subtitleTo: "/device/details/" + id,
                    hint: "route.hint.edit",
                    icon: <RouterOutlined />,
                }}
            />
            <Surface>
                <Form
                    {...{
                        initialValues: device,
                        endpoint: "/device/" + device.id,
                        children: (
                            // Note! Ugly CSS selector. A way of forcing endpoint device collection table to have specific column sizes. This assumes endpointDevices are actually last and their fields have specific order
                            <Box
                                {...{
                                    sx: {
                                        "& .ForgeCollectionTable-root:last-child th:nth-of-type(2)": {
                                            width: "12rem",
                                        },
                                        "& .ForgeCollectionTable-root:last-child th:nth-of-type(3)": {
                                            minWidth: "14rem",
                                        },
                                    },
                                }}
                            >
                                <CrudFieldset
                                    {...{
                                        fields,
                                        backButtonProps: {
                                            onClick: () => navigate("/device/details/" + device.id),
                                        },
                                    }}
                                />
                            </Box>
                        ),
                        changeSubmitValues: (values) => {
                            return changeSubmitValuesCertificateAutomaticBehaviorCollection(values);
                        },
                        onSubmitSuccess: (defaultOnSubmitSuccess) => {
                            defaultOnSubmitSuccess();
                            navigate("/device/details/" + device.id);
                        },
                        fields,
                    }}
                />
            </Surface>
        </>
    );
};

export default DeviceEdit;
