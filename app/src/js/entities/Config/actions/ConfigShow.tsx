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
import { ButtonProps, ColumnActionInterface } from "@arteneo/forge";
import { ConfigInterface } from "~app/entities/Config/definitions";
import { getFormatConfig } from "~app/entities/Config/utilities";
import ResultDialogMonaco from "~app/components/Table/actions/ResultDialogMonaco";

type ConfigShowProps = ColumnActionInterface & ButtonProps;

const ConfigShow = ({ result, ...props }: ConfigShowProps) => {
    if (typeof result === "undefined") {
        throw new Error("ConfigShow component: Missing required result prop");
    }

    const config: ConfigInterface = result as unknown as ConfigInterface;
    const formatConfig = getFormatConfig(config.deviceType, config.feature);

    return (
        <ResultDialogMonaco
            {...{
                result,
                dialogProps: (result) => ({
                    content: result.content,
                    language: formatConfig,
                    // Config can be a mix of JSON and Twig syntax.
                    // We are formatting as JSON and Twig syntax will be broken. Disable formatting on mount.
                    // plain formatting is unaffected as it does not do anything.
                    disableFormatOnMount: true,
                }),
                ...props,
            }}
        />
    );
};

export default ConfigShow;
export { ConfigShowProps };
