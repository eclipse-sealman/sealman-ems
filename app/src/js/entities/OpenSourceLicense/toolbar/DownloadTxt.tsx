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
import { ButtonDownload, ButtonDownloadProps, Optional } from "@arteneo/forge";
import { DownloadOutlined } from "@mui/icons-material";

type DownloadTxtProps = Optional<ButtonDownloadProps, "endpoint">;

const DownloadTxt = (props: DownloadTxtProps) => {
    return (
        <ButtonDownload
            {...{
                endpoint: "/opensourcelicense/download/txt",
                label: "action.licenseDownloadTxt",
                color: "info",
                variant: "contained",
                startIcon: <DownloadOutlined />,
                ...props,
            }}
        />
    );
};

export default DownloadTxt;
export { DownloadTxtProps };
