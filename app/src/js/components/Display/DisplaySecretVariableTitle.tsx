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
import { Box } from "@mui/material";
import { TranslateVariablesInterface } from "@arteneo/forge";
import { useTranslation } from "react-i18next";

interface DisplaySecretVariableTitleProps {
    title: string;
    titleVariables?: TranslateVariablesInterface;
}

const DisplaySecretVariableTitle = ({ title, titleVariables = {} }: DisplaySecretVariableTitleProps) => {
    const { t } = useTranslation();

    return (
        <Box
            {...{
                sx: {
                    py: 0.5,
                    px: 1.5,
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "end",
                    fontWeight: 700,
                    backgroundColor: "grey.100",
                    textAlign: "right",
                    height: "100%",
                    wordBreak: "break-all",
                },
            }}
        >
            {t(title, titleVariables)}
        </Box>
    );
};

export default DisplaySecretVariableTitle;
export { DisplaySecretVariableTitleProps };
