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
import { IconButton, Text, TextProps } from "@arteneo/forge";
import { VisibilityOutlined, VisibilityOffOutlined } from "@mui/icons-material";

interface TextWithObfuscatedValueProps extends TextProps {
    additionalEndAdornment?: React.ReactNode;
}

const TextWithObfuscatedValue = ({ additionalEndAdornment, ...textProps }: TextWithObfuscatedValueProps) => {
    const [obfuscated, setObfuscated] = React.useState<boolean>(true);

    return (
        <Text
            {...{
                ...textProps,
                fieldProps: {
                    type: obfuscated ? "password" : "text",
                    ...(textProps?.fieldProps ?? {}),
                    InputProps: {
                        endAdornment: (
                            <>
                                {additionalEndAdornment}
                                <IconButton
                                    {...{
                                        icon: obfuscated ? <VisibilityOutlined /> : <VisibilityOffOutlined />,
                                        onClick: () => setObfuscated(!obfuscated),
                                    }}
                                />
                            </>
                        ),
                        ...(textProps?.fieldProps?.InputProps ?? {}),
                    },
                },
            }}
        />
    );
};

export default TextWithObfuscatedValue;
export { TextWithObfuscatedValueProps };
