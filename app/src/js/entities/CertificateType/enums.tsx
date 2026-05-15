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

const pkiHashAlgorithm = new Enum(["SHA256", "SHA384", "SHA512"], "enum.configuration.pkiHashAlgorithm.");
export { pkiHashAlgorithm };

const pkiKeyType = new Enum(["RSA2048", "RSA4096", "EC_P_521", "ED448", "ED25519"], "enum.configuration.pkiKeyType.");
export { pkiKeyType };

const pkiType = new Enum(["none", "scep"], "enum.configuration.pkiType.");
export { pkiType };

// certificateBehavior needs to be limited in custom certificates create/edit forms, but has to be expanded in other certificateCategories
const certificateBehaviorLimited = new Enum(["none", "onDemand", "auto"], "enum.configuration.certificateBehavior.");
export { certificateBehaviorLimited };

type CertificateBehaviorType = "none" | "onDemand" | "auto" | "specific";

const certificateBehavior = new Enum(
    ["none", "onDemand", "auto", "specific"],
    "enum.configuration.certificateBehavior."
);
export { certificateBehavior, CertificateBehaviorType };

type CertificateCategoryType = "custom" | "deviceVpn" | "technicianVpn" | "dps" | "edgeCa";

const certificateCategory = new Enum(
    ["custom", "deviceVpn", "technicianVpn", "dps", "edgeCa"],
    "enum.common.certificateCategory."
);
export { CertificateCategoryType, certificateCategory };

const certificateEntity = new Enum(["device", "user"], "enum.common.certificateEntity.");
export { certificateEntity };
