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
import BatchFlag, { BatchFlagProps } from "~app/entities/ImportFileRow/toolbar/BatchFlag";

type BatchReinstallConfig1Props = Optional<BatchFlagProps, "prefix" | "endpoint">;

const BatchReinstallConfig1 = (props: BatchReinstallConfig1Props) => {
    return (
        <BatchFlag
            {...{
                prefix: "batch.importFileRow.reinstallConfig1",
                endpoint: "/importfilerow/batch/reinstallconfig1",
                ...props,
            }}
        />
    );
};

export default BatchReinstallConfig1;
export { BatchReinstallConfig1Props };
