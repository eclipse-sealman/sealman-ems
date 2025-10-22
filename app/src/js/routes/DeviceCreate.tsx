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
import { RouterOutlined } from "@mui/icons-material";
import { Form, useHandleCatch, useLoader } from "@arteneo/forge";
import { useNavigate, useParams } from "react-router-dom";
import { FormikValues } from "formik";
import { DeviceTypeInterface, DeviceTypeCertificateType } from "~app/entities/DeviceType/definitions";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import composeGetFields from "~app/entities/Device/fields";
import { useUser } from "~app/contexts/User";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";
import { changeSubmitValuesCertificateAutomaticBehaviorCollection } from "~app/components/Form/fields/CertificateAutomaticBehaviorCollection";

const DeviceCreate = () => {
    const { isAccessGranted } = useUser();
    const navigate = useNavigate();
    const { deviceTypeId } = useParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [deviceType, setDeviceType] = React.useState<undefined | DeviceTypeInterface>(undefined);

    React.useEffect(() => load(), []);

    const load = () => {
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

    let content = null;

    if (typeof deviceType !== "undefined") {
        const getFields = composeGetFields(deviceType, isAccessGranted({ smartems: true }));

        const adminScepFieldNames = ["useableCertificates"];

        const adminVpnFieldNames = ["virtualSubnetCidr", "masqueradeType", "masquerades", "endpointDevices"];

        const smartemsFieldNames = [
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

        //VPN user cannot create devices - secured on router and controller level
        const fields = getFields(undefined, [
            ...(isAccessGranted({ adminVpn: true }) ? [] : adminVpnFieldNames),
            ...(isAccessGranted({ adminScep: true }) ? [] : adminScepFieldNames),
            ...(isAccessGranted({ admin: true, smartems: true }) ? [] : smartemsFieldNames),
        ]);

        const initialValues: FormikValues = {};
        if (typeof fields.masqueradeType !== "undefined") {
            initialValues.masqueradeType = deviceType.masqueradeType;
        }
        if (typeof fields.virtualSubnetCidr !== "undefined") {
            initialValues.virtualSubnetCidr = deviceType.virtualSubnetCidr;
        }

        initialValues.useableCertificates = deviceType.certificateTypes
            .map((deviceTypeCertificateType: DeviceTypeCertificateType) => {
                if (deviceTypeCertificateType.isCertificateTypeAvailable) {
                    return {
                        certificateType: deviceTypeCertificateType.certificateType,
                    };
                } else {
                    return null;
                }
            })
            .filter((usableCertificate) => usableCertificate !== null);

        content = (
            <Form
                {...{
                    endpoint: "/device/create",
                    initialValues,
                    children: (
                        <CrudFieldset
                            {...{
                                fields,
                                backButtonProps: { onClick: () => navigate(-1) },
                            }}
                        />
                    ),
                    changeSubmitValues: (values) => {
                        values.deviceType = deviceTypeId;
                        return changeSubmitValuesCertificateAutomaticBehaviorCollection(values);
                    },
                    onSubmitSuccess: (defaultOnSubmitSuccess) => {
                        defaultOnSubmitSuccess();
                        navigate("/device/list");
                    },
                    fields,
                }}
            />
        );
    }

    const titleProps: SurfaceTitleProps = {
        title: "route.title.device",
        titleTo: "/device/list",
        subtitle: "route.subtitle.create",
        icon: <RouterOutlined />,
    };

    if (deviceType?.name) {
        titleProps.hint = "route.hint.selectedDeviceType";
        titleProps.hintVariables = { deviceType: deviceType?.name };
    }

    return (
        <>
            <SurfaceTitle {...titleProps} />
            <Surface>{content}</Surface>
        </>
    );
};

export default DeviceCreate;
