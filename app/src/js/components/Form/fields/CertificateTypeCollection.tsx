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
import { FormikValues, FormikProps, useFormikContext } from "formik";
import { Box, FormHelperText, SxProps, Typography } from "@mui/material";
import { FieldInterface, FieldsInterface, useForm } from "@arteneo/forge";
import { useTranslation } from "react-i18next";
import { CertificateTypeInterface } from "~app/entities/Common/definitions";

interface CertificateTypeCollectionSpecificProps {
    indent?: boolean;
    certificateTypeHeader?: string;
    fields: FieldsInterface;
    certificateTypes: CertificateTypeInterface[];
    getFieldProps?: (
        fieldName: string,
        certificateType: CertificateTypeInterface,
        key: string | number
    ) => FieldInterface;
    isRowHidden?: (certificateType: CertificateTypeInterface, key: string | number) => boolean;
}

type CertificateTypeCollectionProps = CertificateTypeCollectionSpecificProps & FieldInterface;

const CertificateTypeCollection = ({
    fields,
    certificateTypes,
    indent = false,
    certificateTypeHeader,
    getFieldProps,
    isRowHidden,
    ...field
}: CertificateTypeCollectionProps) => {
    const { t } = useTranslation();
    const { values, touched, errors, submitCount, registerField, unregisterField }: FormikProps<FormikValues> =
        useFormikContext();
    const { resolveField } = useForm();
    const { name, path, error, help, hidden, validate } = resolveField({
        values,
        touched,
        errors,
        submitCount,
        ...field,
    });

    React.useEffect(() => {
        if (hidden || typeof validate === "undefined") {
            return;
        }

        registerField(path, {
            validate: () => validate,
        });

        return () => {
            unregisterField(path);
        };
    }, [hidden, registerField, unregisterField, path, validate]);

    if (hidden) {
        return null;
    }

    let boxSx: SxProps = { display: "grid" };

    if (indent) {
        boxSx = {
            display: "flex",
            flexDirection: "column",
            mb: 4,
            ml: 1,
            pl: 2,
            borderLeftWidth: 1,
            borderLeftStyle: "dashed",
            borderLeftColor: "grey.400",
        };
    }

    const resolvedIsRowHidden = (certificateType: CertificateTypeInterface, key: string | number) => {
        if (isRowHidden) {
            return isRowHidden(certificateType, key);
        }
        return false;
    };

    const resolvedGetFieldProps = (
        fieldName: string,
        certificateType: CertificateTypeInterface,
        key: string | number
    ) => {
        if (getFieldProps) {
            return getFieldProps(fieldName, certificateType, key);
        }
        return {};
    };

    return (
        <div>
            {help && <FormHelperText>{help}</FormHelperText>}
            {error && <FormHelperText error>{error}</FormHelperText>}

            {certificateTypes.map((certificateType, key) => (
                <React.Fragment key={key}>
                    {!resolvedIsRowHidden(certificateType, key) && (
                        <Box
                            {...{
                                sx: boxSx,
                            }}
                        >
                            {certificateTypeHeader && (
                                <Typography {...{ variant: "h3", sx: { mb: 2 } }}>
                                    {t(certificateTypeHeader, {
                                        certificateType: certificateType.representation,
                                    })}
                                </Typography>
                            )}
                            {Object.keys(fields).map((fieldName) => (
                                <Box key={fieldName} sx={{ display: "grid" }}>
                                    {React.cloneElement(fields[fieldName], {
                                        name: name + "." + key + "." + fieldName,
                                        path: path + "." + key + "." + fieldName,
                                        labelVariables: { certificateType: certificateType.representation },
                                        ...resolvedGetFieldProps(fieldName, certificateType, key),
                                    })}
                                </Box>
                            ))}
                        </Box>
                    )}
                </React.Fragment>
            ))}
        </div>
    );
};

export default CertificateTypeCollection;
export { CertificateTypeCollectionProps, CertificateTypeCollectionSpecificProps };
