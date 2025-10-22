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
import { IconButtonEndpoint } from "@arteneo/forge";
import { AutorenewOutlined } from "@mui/icons-material";
import { FormikProps, FormikValues, useFormikContext } from "formik";
import { AxiosResponse } from "axios";
import { useParams } from "react-router-dom";
import TextWithObfuscatedValue, {
    TextWithObfuscatedValueProps,
} from "~app/components/Form/fields/TextWithObfuscatedValue";

type TextSecretValueProps = TextWithObfuscatedValueProps;

const TextSecretValue = (props: TextSecretValueProps) => {
    const { setFieldValue }: FormikProps<FormikValues> = useFormikContext();
    const { id, deviceTypeSecretId, deviceId } = useParams();

    if (typeof props.name === "undefined") {
        throw new Error("TextSecretValue: Missing name prop. By default it is injected while rendering.");
    }

    const path = props.path ? props.path : props.name;

    const updateSecretValue = (defaultOnSuccess: () => void, response: AxiosResponse) => {
        setFieldValue(path, response.data);
        defaultOnSuccess();
    };

    let endpoint = "/devicesecret/0/generate/secret";
    //edit endpoint
    if (id) {
        endpoint = "/devicesecret/" + id + "/generate/device/secret";
    }
    //create endpoing
    if (deviceTypeSecretId && deviceId) {
        endpoint = "/devicesecret/" + deviceId + "/" + deviceTypeSecretId + "/generate/device/type/secret";
    }

    return (
        <TextWithObfuscatedValue
            {...{
                additionalEndAdornment: (
                    <IconButtonEndpoint
                        {...{
                            icon: <AutorenewOutlined />,
                            onSuccess: updateSecretValue,
                            snackbarLabel: "textSecretValue.snackbar.success",
                            endpoint: endpoint,
                        }}
                    />
                ),
                ...props,
            }}
        />
    );
};

export default TextSecretValue;
export { TextSecretValueProps };
