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

import EnumColor from "~app/classes/EnumColor";

const commandStatus = new EnumColor(
    ["success", "error", "critical", "pending", "expired"],
    ["success", "error", "critical", "info", "warning"],
    "enum.deviceCommand.commandStatus."
);

export { commandStatus };
