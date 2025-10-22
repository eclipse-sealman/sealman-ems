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
import { Form } from "@arteneo/forge";
import { useNavigate, useParams } from "react-router-dom";
import getFields, { clearSubmitValues } from "~app/entities/DeviceTypeSecret/fields";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";
import { FormikValues } from "formik";

const DeviceTypeSecretCreate = () => {
    const navigate = useNavigate();

    const { deviceTypeId } = useParams();

    const fields = getFields();

    const content = (
        <Form
            {...{
                endpoint: "/devicetypesecret/create",
                children: (
                    <CrudFieldset
                        {...{
                            fields,
                            backButtonProps: { onClick: () => navigate(-1) },
                        }}
                    />
                ),
                initialValues: {
                    deviceType: deviceTypeId,
                    secretValueBehaviour: "none",
                    secretValueRenewAfterDays: 14,
                    manualEditRenewReminderAfterDays: 14,
                    secretMinimumLength: 8,
                    secretDigitsAmount: 1,
                    secretUppercaseLettersAmount: 1,
                    secretLowercaseLettersAmount: 1,
                    secretSpecialCharactersAmount: 1,
                },
                onSubmitSuccess: (defaultOnSubmitSuccess) => {
                    defaultOnSubmitSuccess();
                    navigate("/configuration/devicetypesecret/" + deviceTypeId + "/list");
                },
                changeSubmitValues: (values: FormikValues) => {
                    const _values = Object.assign({}, values);

                    clearSubmitValues(_values);

                    return _values;
                },
                fields,
            }}
        />
    );

    const titleProps: SurfaceTitleProps = {
        title: "route.title.configuration.deviceTypeSecret",
        titleTo: "/configuration/devicetypesecret/list",
        subtitle: "route.subtitle.create",
        icon: <VpnKeyOutlined />,
    };

    return (
        <>
            <SurfaceTitle {...titleProps} />
            <Surface>{content}</Surface>
        </>
    );
};

export default DeviceTypeSecretCreate;
