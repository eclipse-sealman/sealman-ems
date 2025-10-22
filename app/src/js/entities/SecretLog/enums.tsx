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

type SecretOperationType =
    | "communicationShow"
    | "communicationRenew"
    | "userShow"
    | "userShowPreviousLog"
    | "userShowUpdatedLog"
    | "userShowCommunicationLog"
    | "userShowConfigLog"
    | "userShowDiagnoseLog"
    | "userClear"
    | "userEdit"
    | "userCreate";

const secretOperation = new EnumColor(
    [
        "communicationShow",
        "communicationRenew",
        "userShow",
        "userShowPreviousLog",
        "userShowUpdatedLog",
        "userShowCommunicationLog",
        "userShowConfigLog",
        "userShowDiagnoseLog",
        "userClear",
        "userEdit",
        "userCreate",
    ],
    ["info", "success", "info", "info", "info", "info", "info", "info", "error", "warning", "warning"],
    "enum.configuration.secretOperation."
);
export { secretOperation, SecretOperationType };
