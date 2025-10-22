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

import { Enum } from "@arteneo/forge";
import EnumColor from "~app/classes/EnumColor";

type StatusType = "pending" | "inProgress" | "success" | "failed";
const status = new EnumColor(
    ["pending", "inProgress", "success", "failed"],
    ["info", "warning", "success", "error"],
    "enum.maintenance.status."
);
export { status, StatusType };

type TypeType = "backup" | "restore" | "backupForUpdate";
const type = new Enum(["backup", "restore", "backupForUpdate"], "enum.maintenance.type.");
export { type, TypeType };
