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
import { FormikValues, FormikProps, useFormikContext, getIn } from "formik";
import { Box, FormHelperText, SxProps, Typography } from "@mui/material";
import { FieldInterface, FieldsInterface, OptionInterface, useForm } from "@arteneo/forge";
import { useTranslation } from "react-i18next";

interface RequiredHasFieldsForCertificateTypesInterface {
    fieldName: string;
    certificateTypes: OptionInterface[];
}

interface FalsifyFieldsOnFalseForCertificateTypeInterface {
    fieldName: string;
    certificateType?: OptionInterface;
    fieldNamesToFalsify: string[];
}

interface FalsifyCollectionFieldsOnFalseInterface {
    fieldName: string;
    collectionFieldNamesToFalsify: string[];
}

interface CertificateTypeCollectionDeviceTypeSpecificProps {
    indent?: boolean;
    certificateTypeHeader?: string;
    showCollectionRowOnTrue?: string;
    fields: FieldsInterface;
    certificateTypes: OptionInterface[];
    requiredHasField?: string;
    requiredHasFieldsForCertificateTypes?: RequiredHasFieldsForCertificateTypesInterface[];
    falsifyFieldsOnFalseForCertificateType?: FalsifyFieldsOnFalseForCertificateTypeInterface[];
    falsifyCollectionFieldsOnFalse?: FalsifyCollectionFieldsOnFalseInterface[];
}

type CertificateTypeCollectionDeviceTypeProps = CertificateTypeCollectionDeviceTypeSpecificProps & FieldInterface;

// Very custom solution for handling DeviceType certificateTypes settings because of fields relationships and conditions required by backend validators
const CertificateTypeCollectionDeviceType = ({
    fields,
    certificateTypes,
    indent = false,
    requiredHasFieldsForCertificateTypes = [],
    falsifyFieldsOnFalseForCertificateType = [],
    falsifyCollectionFieldsOnFalse = [],
    requiredHasField = undefined,
    certificateTypeHeader,
    showCollectionRowOnTrue,
    ...field
}: CertificateTypeCollectionDeviceTypeProps) => {
    const { t } = useTranslation();
    const { values, touched, errors, submitCount, registerField, unregisterField }: FormikProps<FormikValues> =
        useFormikContext();
    const { resolveField } = useForm();
    const { name, path, error, help, hidden, disabled, validate } = resolveField({
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

    const isRowVisible = (key: string | number) => {
        if (showCollectionRowOnTrue == undefined) {
            return true;
        }
        const fieldValue = getIn(values, path + "." + key + "." + showCollectionRowOnTrue, false);
        return fieldValue === true;
    };

    const falsifyFieldsValueOnChange = (
        // eslint-disable-next-line
        setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void,
        checked: boolean,
        onChange: () => void,
        values: FormikValues,
        field: string,
        certificateType: OptionInterface,
        key: string | number
    ) => {
        if (checked) {
            onChange();
            return;
        }
        //Only falsify on false value
        const falsifyFieldProps = falsifyFieldsOnFalseForCertificateType.filter(
            (row: FalsifyFieldsOnFalseForCertificateTypeInterface) => {
                if (row.fieldName != field) {
                    return false;
                }
                return true;
            }
        );

        falsifyFieldProps.forEach((row: FalsifyFieldsOnFalseForCertificateTypeInterface) => {
            if (row.certificateType === undefined) {
                return;
            }
            if (row.certificateType.id !== certificateType.id) {
                return;
            }

            row.fieldNamesToFalsify.forEach((name: string) => {
                if (values[name]) {
                    setFieldValue(name, false);
                }
            });
        });

        const falsifyCollectionFieldProps = falsifyCollectionFieldsOnFalse.filter(
            (row: FalsifyCollectionFieldsOnFalseInterface) => {
                if (row.fieldName != field) {
                    return false;
                }
                return true;
            }
        );

        falsifyCollectionFieldProps.forEach((row: FalsifyCollectionFieldsOnFalseInterface) => {
            row.collectionFieldNamesToFalsify.forEach((name: string) => {
                const fieldPath = path + "." + key + "." + name;
                const fieldValue = getIn(values, fieldPath, false);
                if (fieldValue) {
                    setFieldValue(fieldPath, false);
                }
            });
        });

        onChange();
    };

    const getFieldAdditionalProps = (field: string, certificateType: OptionInterface, key: string | number) => {
        let falsifyOnChange = undefined;

        const foundFalsifyFieldsOnChange = falsifyFieldsOnFalseForCertificateType.find(
            (row: FalsifyFieldsOnFalseForCertificateTypeInterface) => {
                if (row.fieldName != field) {
                    return false;
                }
                return true;
            }
        );

        const foundFalsifyCollectionFieldsOnChange = falsifyCollectionFieldsOnFalse.find(
            (row: FalsifyCollectionFieldsOnFalseInterface) => {
                if (row.fieldName != field) {
                    return false;
                }
                return true;
            }
        );

        if (foundFalsifyFieldsOnChange || foundFalsifyCollectionFieldsOnChange) {
            falsifyOnChange = (
                path: string,
                // eslint-disable-next-line
                setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void,
                event: React.SyntheticEvent,
                checked: boolean,
                onChange: () => void,
                values: FormikValues
            ) => falsifyFieldsValueOnChange(setFieldValue, checked, onChange, values, field, certificateType, key);
        }

        const foundRequired = requiredHasFieldsForCertificateTypes.find(
            (row: RequiredHasFieldsForCertificateTypesInterface) => {
                if (row.fieldName != field) {
                    return false;
                }
                if (
                    row.certificateTypes.find(
                        (requiredCertificateType) => requiredCertificateType.id == certificateType.id
                    )
                ) {
                    return true;
                }
                return false;
            }
        );

        if (foundRequired) {
            return {
                required: true,
                disabled: true,
                help: t("help.certificateTypeRequired"),
                disableTranslateHelp: true,
            };
        }

        if (requiredHasField !== undefined) {
            const resolvedHelp = (values: FormikValues) => {
                if (values[requiredHasField] !== true) {
                    return t("label." + requiredHasField) + " " + t("help.requiredFieldEnableToUse");
                } else {
                    return undefined;
                }
            };
            return {
                disabled: (values: FormikValues) => values[requiredHasField] !== true || disabled,
                help: resolvedHelp,
                disableTranslateHelp: true,
                onChange: falsifyOnChange,
            };
        }

        return { onChange: falsifyOnChange, disabled };
    };

    return (
        <div>
            {help && <FormHelperText>{help}</FormHelperText>}
            {error && <FormHelperText error>{error}</FormHelperText>}

            {certificateTypes.map((certificateType, key) => (
                <React.Fragment key={key}>
                    {isRowVisible(key) && (
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
                            {Object.keys(fields).map((field) => (
                                <Box key={field} sx={{ display: "grid" }}>
                                    {React.cloneElement(fields[field], {
                                        name: name + "." + key + "." + field,
                                        path: path + "." + key + "." + field,
                                        labelVariables: { certificateType: certificateType.representation },
                                        ...getFieldAdditionalProps(field, certificateType, key),
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

export default CertificateTypeCollectionDeviceType;
export { CertificateTypeCollectionDeviceTypeProps, CertificateTypeCollectionDeviceTypeSpecificProps };
