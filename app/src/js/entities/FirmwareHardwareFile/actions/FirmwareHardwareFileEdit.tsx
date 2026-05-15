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
import ResultEdit, { ResultEditProps } from "~app/components/Table/actions/ResultEdit";

type FirmwareHardwareFileEditProps = ResultEditProps;

const FirmwareHardwareFileEdit = ({ result, ...props }: FirmwareHardwareFileEditProps) => {
    if (typeof result === "undefined") {
        throw new Error("FirmwareHardwareFileEdit component: Missing required result prop");
    }

    let denyKey: ResultEditProps["denyKey"] = undefined;
    let denyBehavior: ResultEditProps["denyBehavior"] = undefined;
    let to: ResultEditProps["to"] = undefined;

    if (result?.sourceType === "upload") {
        denyKey = "editSourceUpload";
        denyBehavior = "disable";
        // This URL does not exist as button will be always disabled. Used only for consistency
        to = "/firmwarehardwarefile/edit/upload/" + result.id;
    }

    if (result?.sourceType === "externalUrl") {
        denyKey = "editSourceExternalUrl";
        denyBehavior = "hide";
        to = "/firmwarehardwarefile/edit/externalurl/" + result.id;
    }

    return (
        <ResultEdit
            {...{
                result,
                denyKey,
                denyBehavior,
                to,
                ...props,
            }}
        />
    );
};

export default FirmwareHardwareFileEdit;
export { FirmwareHardwareFileEditProps };
