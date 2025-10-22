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
import { SecurityOutlined } from "@mui/icons-material";
import getColumns from "~app/entities/CertificateType/columns";
import getFilters from "~app/entities/CertificateType/filters";
import composeGetFields from "~app/entities/CertificateType/fields";
import Builder from "~app/components/Crud/Builder";
import { ConfigurationChangeInterface } from "~app/routes/Configuration/ConfigurationChange";
import { FormikValues } from "formik";
import { FieldsInterface, filterInitialValues, transformInitialValues } from "@arteneo/forge";
import { AxiosResponse } from "axios";

const certificateTypeConfiguration: ConfigurationChangeInterface = {
    endpoint: "/certificatetype",
    title: "certificateType",
    to: "/certificatetype/list",
    icon: <SecurityOutlined />,
};

const CertificateType = () => {
    const [predefinedCertificateCategoryForm, setPredefinedCertificateCategoryForm] = React.useState<boolean>(false);

    const columns = getColumns();
    const filters = getFilters();
    const getEditFields = composeGetFields(predefinedCertificateCategoryForm, true);
    const editFields = getEditFields();
    const getCreateFields = composeGetFields(false, false);
    const createFields = getCreateFields();

    const changeSubmitValues = (values: FormikValues, editAction: boolean) => {
        const _values = Object.assign({}, values);

        delete _values.certificateCategory;

        if (editAction) {
            delete _values.certificateEntity;
        }

        if (!_values.uploadEnabled) {
            _values.deleteEnabled = false;
        }
        //SCEP setup can be done regardless of license, but without required license CertificateType will not be available for use
        if (_values.pkiType != "scep") {
            delete _values.scepVerifyServerSslCertificate;
            delete _values.scepUrl;
            delete _values.scepCrlUrl;
            delete _values.scepRevocationUrl;
            delete _values.scepTimeout;
            delete _values.scepRevocationBasicAuthUser;
            delete _values.scepRevocationBasicAuthPassword;
            delete _values.scepHashFunction;
            delete _values.scepKeyLength;
        }

        return _values;
    };

    return (
        <Builder
            {...{
                endpointPrefix: "/certificatetype",
                title: "route.title.configuration.certificateType",
                icon: <SecurityOutlined />,
                listProps: {
                    columns,
                    filters,
                    hasCreate: true,
                    hasEdit: true,
                },
                createProps: {
                    fields: createFields,
                    initialValues: {
                        enabled: true,
                        downloadEnabled: true,
                        uploadEnabled: true,
                        deleteEnabled: true,
                        pkiEnabled: false,
                        certificateEntity: "device",
                        enabledBehaviour: "none",
                        disabledBehaviour: "none",
                        pkiType: "none",
                        scepTimeout: 5,
                        scepVerifyServerSslCertificate: false,
                        scepHashFunction: "SHA512",
                        scepKeyLength: "4096",
                    },
                    changeSubmitValues: (values: FormikValues) => changeSubmitValues(values, false),
                },
                editProps: {
                    fields: editFields,
                    changeSubmitValues: (values: FormikValues) => changeSubmitValues(values, true),
                    processInitialValues: (
                        fields: FieldsInterface,
                        initialValues?: FormikValues,
                        response?: AxiosResponse
                    ) => {
                        setPredefinedCertificateCategoryForm(initialValues?.certificateCategory !== "custom");
                        return transformInitialValues(
                            fields,
                            filterInitialValues(fields, initialValues, response?.data)
                        );
                    },
                },
            }}
        />
    );
};

export default CertificateType;
export { certificateTypeConfiguration };
