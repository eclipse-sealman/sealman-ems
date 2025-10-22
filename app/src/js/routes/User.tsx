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
import { PersonOutlined } from "@mui/icons-material";
import { FormikValues } from "formik";
import { useNavigate } from "react-router-dom";
import getColumns from "~app/entities/User/columns";
import getFilters from "~app/entities/User/filters";
import getFields from "~app/entities/User/fields";
import getChangePasswordFields from "~app/entities/User/changePasswordFields";
import Builder from "~app/components/Crud/Builder";
import { useConfiguration } from "~app/contexts/Configuration";
import { useUser } from "~app/contexts/User";
import UserEditFieldset from "~app/fieldsets/UserEditFieldset";
import { changeSubmitValuesCertificateAutomaticBehaviorCollection } from "~app/components/Form/fields/CertificateAutomaticBehaviorCollection";
import BuilderToolbar from "~app/components/Table/toolbar/BuilderToolbar";
import BatchButtonExpand from "~app/components/Table/toolbar/BatchButtonExpand";
import BatchDisable from "~app/entities/User/toolbar/BatchDisable";
import BatchEnable from "~app/entities/User/toolbar/BatchEnable";

const User = () => {
    const navigate = useNavigate();
    const { isTotpEnabled, isRadiusEnabled, singleSignOn } = useConfiguration();
    const { isAccessGranted } = useUser();

    const skipNames: string[] = [];
    if (!isTotpEnabled) {
        skipNames.push("totpEnabled");
    }

    if (!isRadiusEnabled) {
        skipNames.push("radiusUser");
    }

    if (!isAccessGranted({ adminScep: true })) {
        skipNames.push("certificateBehaviours");
    }

    if (singleSignOn === "disabled") {
        skipNames.push("ssoUser");
    }

    if (!isAccessGranted({ adminVpn: true })) {
        skipNames.push("certificate");
        skipNames.push("roleVpn");
        skipNames.push("roleVpnEndpointDevices");
    }

    const columns = getColumns(undefined, skipNames);
    const filters = getFilters(undefined, skipNames);

    const createFields = getFields(undefined, skipNames);
    const editFields = getFields(
        [
            "username",
            "roleAdmin",
            "roleSmartems",
            "roleVpn",
            "roleVpnEndpointDevices",
            "accessTags",
            "enabled",
            "enabledExpireAt",
            "certificateBehaviours",
            "disablePasswordExpire",
            "totpEnabled",
        ],
        skipNames
    );

    const changePasswordFields = getChangePasswordFields();

    const changeSubmitValues = (values: FormikValues) => {
        //Making sure that fields onChange handler will not mess submitted values
        const _values = Object.assign({}, values);
        if (!isAccessGranted({ adminScep: true })) {
            delete _values.certificateBehaviours;
        }

        if (!isAccessGranted({ adminVpn: true })) {
            delete _values.roleVpn;
            delete _values.roleVpnEndpointDevices;
        }

        if (!_values.roleVpn) {
            delete _values.roleVpnEndpointDevices;
        }

        return changeSubmitValuesCertificateAutomaticBehaviorCollection(_values);
    };

    return (
        <Builder
            {...{
                endpointPrefix: "/user",
                title: "route.title.user",
                icon: <PersonOutlined />,
                listProps: {
                    columns,
                    filters,
                    enableBatchSelect: true,
                    toolbar: (
                        <BuilderToolbar
                            {...{
                                render: ({ createAction }) => (
                                    <>
                                        <BatchButtonExpand>
                                            <BatchDisable />
                                            <BatchEnable />
                                        </BatchButtonExpand>
                                        <>{createAction}</>
                                    </>
                                ),
                            }}
                        />
                    ),
                },
                createProps: {
                    initializeEndpoint: "/user/initial/useable/certificates",
                    fields: createFields,
                    changeSubmitValues: changeSubmitValues,
                },
                editProps: {
                    fields: editFields,
                    changeSubmitValues: changeSubmitValues,
                    children: (
                        <UserEditFieldset
                            {...{
                                fields: editFields,
                                backButtonProps: { onClick: () => navigate("/user/list") },
                            }}
                        />
                    ),
                },
                changePasswordProps: {
                    fields: changePasswordFields,
                },
            }}
        />
    );
};

export default User;
