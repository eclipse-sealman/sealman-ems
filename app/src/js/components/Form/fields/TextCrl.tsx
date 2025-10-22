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
import { Text, TextProps } from "@arteneo/forge";
import { RemoveRedEyeOutlined } from "@mui/icons-material";
import { FormikProps, FormikValues, getIn, useFormikContext } from "formik";
import IconButtonDialogMonaco from "~app/components/Common/IconButtonDialogMonaco";

interface TextCrlProps extends TextProps {
    verifyServerSslCertificatePath: string;
    scepTimeoutPath: string;
}

const TextCrl = ({ verifyServerSslCertificatePath, scepTimeoutPath, ...props }: TextCrlProps) => {
    const { values }: FormikProps<FormikValues> = useFormikContext();

    if (typeof props.name === "undefined") {
        throw new Error("TextCrl: Missing name prop. By default it is injected while rendering.");
    }

    const path = props.path ? props.path : props.name;
    const value = getIn(values, path, undefined);
    const verifyServerSslCertificate = getIn(values, verifyServerSslCertificatePath, false);
    const scepTimeout = getIn(values, scepTimeoutPath, 5);

    return (
        <Text
            {...{
                fieldProps: {
                    InputProps: {
                        endAdornment: (
                            <IconButtonDialogMonaco
                                {...{
                                    disabled: value !== undefined && value != "" ? false : true,
                                    icon: <RemoveRedEyeOutlined />,
                                    dialogProps: {
                                        title: "certificateType.scepCrl.dialogTitle",
                                        initializeEndpoint: {
                                            method: "post",
                                            url: "/certificatetype/scep/crl/content",
                                            data: {
                                                url: value,
                                                verifyServerSslCertificate,
                                                scepTimeout,
                                            },
                                        },
                                        content: (payload) => payload,
                                    },
                                }}
                            />
                        ),
                    },
                },
                ...props,
            }}
        />
    );
};

export default TextCrl;
export { TextCrlProps };
