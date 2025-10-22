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
import { FieldPlaceholderInterface, OptionsType, Select, Text } from "@arteneo/forge";
import { useTranslation } from "react-i18next";
import { FormikProps, FormikValues, getIn, useFormikContext } from "formik";
import { Tooltip } from "@mui/material";
import { HelpOutline } from "@mui/icons-material";
import { formatIpSortable } from "~app/utilities/format";

interface VirtualIpHostPartProps extends FieldPlaceholderInterface {
    virtualSubnetIpSortable?: number;
    getVirtualSubnetCidr?: (values: FormikValues) => number;
    getEndpointDevices?: (values: FormikValues) => FormikValues[];
}

const VirtualIpHostPart = ({
    virtualSubnetIpSortable,
    getVirtualSubnetCidr = (values) => getIn(values, "virtualSubnetCidr", 32),
    getEndpointDevices = (values) => getIn(values, "endpointDevices", []),
    path,
    name,
    ...props
}: VirtualIpHostPartProps) => {
    const { t } = useTranslation();
    const { values, setFieldValue }: FormikProps<FormikValues> = useFormikContext();
    const [options, setOptions] = React.useState<OptionsType>([]);
    const [subnetSize, setSubnetSize] = React.useState(0);

    if (typeof name === "undefined") {
        throw new Error("VirtualIpHostPart component. Missing name prop. By default it is injected while rendering.");
    }

    const fieldName = name.split(".").pop() ?? "";
    const virtualSubnetCidr = getVirtualSubnetCidr(values);
    const endpointDevices: FormikValues[] = Object.values(getEndpointDevices(values));
    const useEffectDependency = endpointDevices.map(
        (endpointDevice) => endpointDevice?.name + "" + endpointDevice?.[fieldName]
    );

    const resolvedPath = path ? path : name;
    const value = getIn(values, resolvedPath, "");
    const numberValue = parseInt(value, 10);
    React.useEffect(() => updateSubnetSize(), [virtualSubnetCidr]);
    React.useEffect(() => buildOptions(), [subnetSize, useEffectDependency.join()]);

    // We have to use <Select /> instead of <Text /> for many options (for UI and performance reasons)
    const isTextField = subnetSize >= 64 ? true : false;

    const updateSubnetSize = () => {
        const subnetSize = Math.pow(2, 32 - virtualSubnetCidr);
        setSubnetSize(subnetSize);

        if (numberValue <= 0 && numberValue >= subnetSize) {
            setFieldValue(resolvedPath, "");
        }
    };

    const buildOptions = () => {
        if (isTextField) {
            return;
        }

        const options: OptionsType = [
            {
                // This is intentionally a curated string to avoid matching it with a value
                id: "0-disabled",
                disabled: true,
                representation: t("virtualIpHostPart.optionZeroDisabled"),
            },
        ];

        const hasVirtualSubnetIp = typeof virtualSubnetIpSortable !== "undefined" ? true : false;

        const labelPrefix = hasVirtualSubnetIp
            ? "virtualIpHostPart.virtualSubnetIpPresent."
            : "virtualIpHostPart.virtualSubnetIpEmpty.";

        for (let i = 1; i < subnetSize; i = i + 1) {
            const endpointDevice = endpointDevices.find(
                (endpointDevice) => parseInt(endpointDevice?.[fieldName], 10) === i
            );

            const virtualIp = formatIpSortable(
                hasVirtualSubnetIp ? (virtualSubnetIpSortable as number) + i : undefined
            );
            let label = labelPrefix;

            const disabled = numberValue !== i && endpointDevice ? true : false;
            if (disabled) {
                if (endpointDevice?.name) {
                    label += "optionDisabled";
                } else {
                    label += "optionDisabledUnnamed";
                }
            } else {
                label += "option";
            }

            options.push({
                id: i,
                disabled,
                representation: t(label, {
                    number: i,
                    name: endpointDevice?.name,
                    virtualIp,
                }),
            });
        }

        setOptions(options);
    };

    return (
        <>
            {isTextField ? (
                <Text
                    {...{
                        fieldProps: {
                            InputProps: {
                                endAdornment: (
                                    <Tooltip {...{ title: t("virtualIpHostPart.text.tooltip", { subnetSize }) }}>
                                        <HelpOutline />
                                    </Tooltip>
                                ),
                            },
                        },
                        path,
                        name,
                        ...props,
                    }}
                />
            ) : (
                <Select
                    {...{
                        options,
                        disableTranslateOption: true,
                        path,
                        name,
                        ...props,
                    }}
                />
            )}
        </>
    );
};

export default VirtualIpHostPart;
export { VirtualIpHostPartProps };
