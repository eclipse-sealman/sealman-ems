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
import { UploadResult } from "@uppy/core";
import { UploadSingleInputFile, UploadSingleInputFileProps } from "@arteneo/forge-uppy";
import { uppyTusOptions } from "~app/utilities/uppy";
import { FormikValues, useFormikContext } from "formik";

interface FirmwareFilepathProps extends UploadSingleInputFileProps {
    enableGuessVersion?: boolean;
}

const FirmwareFilepath = ({ enableGuessVersion = false, ...uploadSingleInputFileProps }: FirmwareFilepathProps) => {
    const { values, setFieldValue } = useFormikContext<FormikValues>();

    return (
        <UploadSingleInputFile
            {...{
                uppyTusOptions,
                modifyUppy: (uppy) => {
                    if (!enableGuessVersion) {
                        return;
                    }

                    uppy.on("complete", (result: UploadResult) => {
                        // We are working with a single upload. Always take first element
                        const fileName = result.successful?.[0]?.name;
                        if (fileName) {
                            if (!values["name"]) {
                                setFieldValue("name", fileName);
                            }

                            if (!values["version"]) {
                                // fileName is slugified (including converting to lowercase)
                                const parts = fileName.split("-v");
                                const versionPart = parts[1];
                                if (versionPart) {
                                    let version = versionPart.replace(".bin", "");
                                    version = version.replace(".pkg", "");

                                    setFieldValue("version", version);
                                }
                            }
                        }
                    });
                },
                ...uploadSingleInputFileProps,
            }}
        />
    );
};

export default FirmwareFilepath;
export { FirmwareFilepathProps };
