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

type FieldRequirementType = "unused" | "optional" | "requiredInCommunication" | "required";
const fieldRequirement = new Enum(
    ["unused", "optional", "requiredInCommunication", "required"],
    "enum.common.fieldRequirement."
);

const isFieldHidden = (fieldRequirement: FieldRequirementType) => {
    if (fieldRequirement == "unused") {
        return true;
    }
    return false;
};

const isFieldRequired = (fieldRequirement: FieldRequirementType) => {
    if (fieldRequirement == "required") {
        return true;
    }
    return false;
};

export { fieldRequirement, FieldRequirementType, isFieldHidden, isFieldRequired };
