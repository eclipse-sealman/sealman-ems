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
import { ButtonDownload, ButtonDownloadProps } from "@arteneo/forge";
import { DownloadOutlined } from "@mui/icons-material";

const ButtonVpnDownloadConfig = ({ ...props }: ButtonDownloadProps) => {
    return (
        <ButtonDownload
            {...{
                label: "action.downloadOvpn",
                variant: "contained",
                color: "success",
                size: "small",
                denyKey: "downloadVpnConfig",
                startIcon: <DownloadOutlined />,
                ...props,
            }}
        />
    );
};

export default ButtonVpnDownloadConfig;
export { ButtonDownloadProps as ButtonVpnDownloadConfigProps };
