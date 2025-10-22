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
import BatchFlag, { BatchFlagProps } from "~app/entities/Device/toolbar/BatchFlag";

type BatchReinstallConfig2Props = Optional<BatchFlagProps, "prefix" | "endpoint">;

const BatchReinstallConfig2 = (props: BatchReinstallConfig2Props) => {
    return (
        <BatchFlag
            {...{
                prefix: "batch.device.reinstallConfig2",
                endpoint: "/device/batch/reinstallconfig2",
                ...props,
            }}
        />
    );
};

export default BatchReinstallConfig2;
export { BatchReinstallConfig2Props };
