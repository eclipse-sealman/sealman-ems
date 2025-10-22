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

type SecretValueBehaviourType = "none" | "generate" | "renew" | "generateRenew";
const secretValueBehaviour = new Enum(
    ["none", "generate", "renew", "generateRenew"],
    "enum.deviceTypeSecret.secretValueBehaviour."
);

// eslint-disable-next-line
const isRenew = (value: any): boolean => {
    if (typeof value !== "string") {
        return false;
    }

    switch (value) {
        case "renew":
        case "generateRenew":
            return true;
        case "none":
        case "generate":
            return false;
        default:
            throw new Error(`Unsupported enum "${value}"`);
    }
};

// eslint-disable-next-line
const isGenerate = (value: any): boolean => {
    if (typeof value !== "string") {
        return false;
    }

    switch (value) {
        case "generate":
        case "generateRenew":
            return true;
        case "none":
        case "renew":
            return false;
        default:
            throw new Error(`Unsupported enum "${value}"`);
    }
};

export { secretValueBehaviour, SecretValueBehaviourType, isRenew, isGenerate };
