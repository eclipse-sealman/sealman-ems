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

type ParseStatusType = "valid" | "warning" | "invalid";
const parseStatus = new Enum(["valid", "warning", "invalid"], "enum.importFileRow.parseStatus.");
export { parseStatus, ParseStatusType };

type ImportStatusType = "pending" | "success" | "warning" | "error";
const importStatus = new Enum(["pending", "success", "warning", "error"], "enum.importFileRow.importStatus.");
export { importStatus, ImportStatusType };
