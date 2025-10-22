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
import { Optional } from "@arteneo/forge";
import ResultDialogMonaco, { ResultDialogMonacoProps } from "~app/components/Table/actions/ResultDialogMonaco";

type LicenseContentShowProps = Optional<ResultDialogMonacoProps, "dialogProps">;

const LicenseContentShow = ({ result, ...props }: LicenseContentShowProps) => {
    if (typeof result === "undefined") {
        throw new Error("LicenseContentShow component: Missing required result prop");
    }

    return (
        <ResultDialogMonaco
            {...{
                result,
                label: "licenseContentShow.action",
                dialogProps: (result) => ({
                    title: "licenseContentShow.dialog.title",
                    content: result.licenseContent,
                }),
                ...props,
            }}
        />
    );
};

export default LicenseContentShow;
export { LicenseContentShowProps };
