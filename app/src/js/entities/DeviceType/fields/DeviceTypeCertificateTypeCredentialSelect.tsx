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
import { OptionInterface, OptionsType, Select, SelectProps } from "@arteneo/forge";
import { useDeepCompareEffectNoCheck } from "use-deep-compare-effect";

interface DeviceTypeCertificateTypeCredentialSelectProps extends Omit<SelectProps, "options"> {
    certificateTypes: OptionInterface[];
}
// Custom solution for handling DeviceTypeCertificateTypeCredential field - related to enabled certificate types
// Only to use in device type form
const DeviceTypeCertificateTypeCredentialSelect = ({
    certificateTypes,
    ...props
}: DeviceTypeCertificateTypeCredentialSelectProps) => {
    const { values, setFieldValue }: FormikProps<FormikValues> = useFormikContext();

    const [options, setOptions] = React.useState<OptionsType>([]);

    useDeepCompareEffectNoCheck(() => filterOptions(), [values?.certificateTypes]);

    const filterOptions = () => {
        if (Array.isArray(values?.certificateTypes)) {
            const filteredOptions = values?.certificateTypes
                .map((certificateType) => {
                    if (certificateType.hasCertificateType) {
                        if (certificateType.certificateType?.id) {
                            return {
                                id: certificateType.certificateType.id,
                                representation: certificateType.certificateType.representation,
                            } as OptionInterface;
                        } else {
                            return certificateTypes.find(
                                (requiredCertificateType) =>
                                    requiredCertificateType.id === certificateType.certificateType
                            );
                        }
                    }

                    return undefined;
                })
                .filter((option) => option !== undefined);

            setOptions(filteredOptions as OptionsType);
        }

        const value = getIn(values, "deviceTypeCertificateTypeCredential", undefined);
        if (typeof value === "object") {
            setFieldValue("deviceTypeCertificateTypeCredential", value.id);
        }
    };

    return <Select {...{ options, disableTranslateOption: true, ...props }} />;
};

export default DeviceTypeCertificateTypeCredentialSelect;
export { DeviceTypeCertificateTypeCredentialSelectProps };
