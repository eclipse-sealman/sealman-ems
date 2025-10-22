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
import { ResultInterface } from "@arteneo/forge";
import ResultDownload, { ResultDownloadProps } from "~app/components/Table/actions/ResultDownload";

type FirmwareDownloadProps = Omit<ResultDownloadProps, "endpoint">;

const FirmwareDownload = ({ result, ...props }: FirmwareDownloadProps) => {
    if (result?.sourceType !== "upload") {
        return null;
    }

    return (
        <ResultDownload
            {...{
                result,
                endpoint: (result: ResultInterface) => result?.downloadUrl,
                ...props,
            }}
        />
    );
};

export default FirmwareDownload;
export { FirmwareDownloadProps };
