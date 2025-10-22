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
import { AXIOS_CANCELLED_UNMOUNTED, Optional, RadioEnum, RadioEnumProps, useHandleCatch } from "@arteneo/forge";
import axios from "axios";
import { Alert } from "@mui/material";
import { useTranslation } from "react-i18next";
import { FormikValues, FormikProps, useFormikContext, getIn } from "formik";
import { masqueradeType as masqueradeTypeEnum } from "~app/enums/MasqueradeType";

interface MasqueradeRadioEnumProps extends Optional<RadioEnumProps, "enum"> {
    masqueradesField?: string;
}

interface MasqueradeDefaultSubnetsInterface {
    devicesVpnNetworks?: string;
    techniciansVpnNetworks?: string;
}

const MasqueradeRadioEnum = ({
    masqueradesField = "masquerades",
    name,
    path,
    ...radioEnumProps
}: MasqueradeRadioEnumProps) => {
    const { t } = useTranslation();
    const handleCatch = useHandleCatch();
    const [defaultSubnets, setDefaultSubnets] = React.useState<MasqueradeDefaultSubnetsInterface>({});
    const { values }: FormikProps<FormikValues> = useFormikContext();

    React.useEffect(() => load(), []);

    const load = () => {
        const axiosSource = axios.CancelToken.source();

        axios
            .get("/options/masquerade/default/subnets")
            .then((response) => setDefaultSubnets(response.data))
            .catch((error) => handleCatch(error));

        return () => {
            axiosSource.cancel(AXIOS_CANCELLED_UNMOUNTED);
        };
    };

    if (typeof name === "undefined") {
        throw new Error("MasqueradeRadioEnum component: Missing name prop. By default it is injected while rendering.");
    }

    const masqueradeType = getIn(values, path ? path : name, "");
    const defaultSubnetsArray: string[] = [];
    if (defaultSubnets?.devicesVpnNetworks) {
        defaultSubnetsArray.push(defaultSubnets?.devicesVpnNetworks);
    }

    if (defaultSubnets?.techniciansVpnNetworks) {
        defaultSubnetsArray.push(defaultSubnets?.techniciansVpnNetworks);
    }

    return (
        <>
            <RadioEnum
                {...{
                    onChange: (
                        path: string,
                        // eslint-disable-next-line
                        setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void,
                        event: React.ChangeEvent<HTMLInputElement>,
                        value: string,
                        onChange: () => void
                    ) => {
                        onChange();

                        if (value === "advanced") {
                            setFieldValue(
                                masqueradesField,
                                defaultSubnetsArray.map((subnet) => ({
                                    subnet,
                                }))
                            );
                        } else {
                            setFieldValue(masqueradesField, []);
                        }
                    },
                    enum: masqueradeTypeEnum,
                    name,
                    path,
                    ...radioEnumProps,
                }}
            />
            {masqueradeType === "default" && (
                <>
                    {!defaultSubnets?.devicesVpnNetworks && (
                        <Alert severity="warning">{t("masqueradeRadioEnum.default.deviceVpnNetworksEmpty")}</Alert>
                    )}
                    {!defaultSubnets?.techniciansVpnNetworks && (
                        <Alert severity="warning">{t("masqueradeRadioEnum.default.techniciansVpnNetworksEmpty")}</Alert>
                    )}
                    {(defaultSubnets?.devicesVpnNetworks || defaultSubnets?.techniciansVpnNetworks) && (
                        <Alert severity="info">
                            {t("masqueradeRadioEnum.default.defaultSubnets", {
                                subnets: defaultSubnetsArray.join(", "),
                            })}
                        </Alert>
                    )}
                </>
            )}
        </>
    );
};

export default MasqueradeRadioEnum;
export { MasqueradeDefaultSubnetsInterface, MasqueradeRadioEnumProps };
