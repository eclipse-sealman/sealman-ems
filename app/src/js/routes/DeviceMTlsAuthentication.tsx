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
import { KeyOutlined } from "@mui/icons-material";
import { FormikValues } from "formik";
import getColumns from "~app/entities/DeviceMTlsAuthentication/columns";
import getFilters from "~app/entities/DeviceMTlsAuthentication/filters";
import getFields from "~app/entities/DeviceMTlsAuthentication/fields";
import Builder from "~app/components/Crud/Builder";

const DeviceMTlsAuthentication = () => {
    const columns = getColumns();
    const filters = getFilters();
    const fields = getFields();

    const changeSubmitValues = (values: FormikValues) => {
        const _values = Object.assign({}, values);

        // Clean up CRL fields based on crlType
        if (_values.crlType !== "pem") {
            delete _values.certificateCrl;
        }

        if (_values.crlType !== "url") {
            delete _values.certificateCrlUrl;
        }

        return _values;
    };

    return (
        <Builder
            {...{
                endpointPrefix: "/devicemtlsauthentication",
                title: "route.title.deviceMTlsAuthentication",
                icon: <KeyOutlined />,
                listProps: {
                    columns,
                    filters,
                },
                createProps: {
                    fields,
                    initialValues: {
                        crlType: "none",
                    },
                    changeSubmitValues: changeSubmitValues,
                },
                editProps: {
                    fields,
                    changeSubmitValues: changeSubmitValues,
                },
            }}
        />
    );
};

export default DeviceMTlsAuthentication;
