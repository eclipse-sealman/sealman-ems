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
import { ColumnActionInterface } from "@arteneo/forge";
import EntityButtonInterface from "~app/definitions/EntityButtonInterface";
import ButtonVpnDownloadConfig, { ButtonVpnDownloadConfigProps } from "~app/components/Common/ButtonVpnDownloadConfig";

type ResultVpnDownloadConfigProps = Omit<ButtonVpnDownloadConfigProps, "endpoint"> &
    EntityButtonInterface &
    ColumnActionInterface;

const ResultVpnDownloadConfig = ({
    result,
    entityPrefix,
    denyKey = "downloadVpnConfig",
    ...props
}: ResultVpnDownloadConfigProps) => {
    if (typeof result === "undefined") {
        throw new Error("ResultVpnDownloadConfig component: Missing required result prop");
    }

    if (result?.deny?.[denyKey]?.endsWith(".accessDenied")) {
        return null;
    }

    if (result?.deny?.[denyKey]?.endsWith(".notAvailable")) {
        return null;
    }

    return (
        <ButtonVpnDownloadConfig
            {...{
                denyKey,
                deny: result?.deny,
                endpoint: "/" + entityPrefix + "/" + result.id + "/download/vpn/config",
                ...props,
            }}
        />
    );
};

export default ResultVpnDownloadConfig;
export { ResultVpnDownloadConfigProps };
