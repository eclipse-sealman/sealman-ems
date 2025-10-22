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

const routerIdentifier = new Enum(["serial", "imsi"], "enum.configuration.routerIdentifier.");
export { routerIdentifier };

const totpAlgorithm = new Enum(["sha1", "sha256", "sha512"], "enum.configuration.totpAlgorithm.");
export { totpAlgorithm };

const totpWindow = new Enum(["1", "3", "5"], "enum.configuration.totpWindow.");
export { totpWindow };

const totpSecretLength = new Enum(["16", "32", "64", "128"], "enum.configuration.totpSecretLength.");
export { totpSecretLength };

const radiusAuthenticationProtocol = new Enum(["pap", "chap"], "enum.configuration.radiusAuthenticationProtocol.");
export { radiusAuthenticationProtocol };

type RadiusUserRoleType = "admin" | "smartems" | "vpn" | "smartemsVpn";
const radiusUserRole = new Enum(["admin", "smartems", "vpn", "smartemsVpn"], "enum.configuration.radiusUserRole.");
export { radiusUserRole, RadiusUserRoleType };

type SingleSignOnType = "disabled" | "microsoftOidc";
const singleSignOn = new Enum(["disabled", "microsoftOidc"], "enum.configuration.singleSignOn.");
export { singleSignOn, SingleSignOnType };

type MicrosoftOidcCredentialType = "clientSecret" | "certificateUpload" | "certificateGenerate";
const microsoftOidcCredential = new Enum(
    ["clientSecret", "certificateUpload", "certificateGenerate"],
    "enum.configuration.microsoftOidcCredential."
);
export { microsoftOidcCredential, MicrosoftOidcCredentialType };

type MicrosoftOidcRoleType = "admin" | "smartems" | "vpn" | "smartemsVpn";
const microsoftOidcRole = new Enum(
    ["admin", "smartems", "vpn", "smartemsVpn"],
    "enum.configuration.microsoftOidcRole."
);
export { microsoftOidcRole, MicrosoftOidcRoleType };
