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

type BatchReinstallFirmware1Props = Optional<BatchFlagProps, "prefix" | "endpoint">;

const BatchReinstallFirmware1 = (props: BatchReinstallFirmware1Props) => {
    return (
        <BatchFlag
            {...{
                prefix: "batch.device.reinstallFirmware1",
                endpoint: "/device/batch/reinstallfirmware1",
                ...props,
            }}
        />
    );
};

export default BatchReinstallFirmware1;
export { BatchReinstallFirmware1Props };
