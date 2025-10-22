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

type BatchReinstallFirmware2Props = Optional<BatchFlagProps, "prefix" | "endpoint">;

const BatchReinstallFirmware2 = (props: BatchReinstallFirmware2Props) => {
    return (
        <BatchFlag
            {...{
                prefix: "batch.device.reinstallFirmware2",
                endpoint: "/device/batch/reinstallfirmware2",
                ...props,
            }}
        />
    );
};

export default BatchReinstallFirmware2;
export { BatchReinstallFirmware2Props };
