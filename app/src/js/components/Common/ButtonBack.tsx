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
import { Button, ButtonProps } from "@arteneo/forge";
import { KeyboardArrowLeftOutlined } from "@mui/icons-material";
import { useNavigate } from "react-router-dom";

const ButtonBack = (props: ButtonProps) => {
    const navigate = useNavigate();

    return (
        <Button
            {...{
                label: "action.back",
                startIcon: <KeyboardArrowLeftOutlined {...{ sx: { mr: -0.5 } }} />,
                size: "small",
                // Color is further overridden by sx prop
                color: "success",
                variant: "contained",
                onClick: () => {
                    // TODO Arek How to handle -1 when this is first page? fallback to homepage?
                    navigate(-1);
                },
                ...props,
                sx: {
                    backgroundColor: "transparent",
                    color: "grey.500",
                    borderWidth: 1,
                    borderStyle: "solid",
                    borderColor: "grey.400",
                    py: "2px",
                    "&:hover": {
                        backgroundColor: "transparent",
                        color: "grey.600",
                        borderColor: "grey.500",
                    },
                    ...(props?.sx ?? {}),
                },
            }}
        />
    );
};

export default ButtonBack;
export { ButtonProps as ButtonBackProps };
