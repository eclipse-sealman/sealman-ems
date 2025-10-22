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

const altNameType = new Enum(["DNS", "IP", "email", "URI"], "enum.deviceType.altNameType.");
export { altNameType };

const certificateEncoding = new Enum(["hex", "oneLinePem"], "enum.deviceType.certificateEncoding.");
export { certificateEncoding };

type FormatConfigType = "plain" | "json";
const formatConfig = new Enum(["plain", "json"], "enum.deviceType.formatConfig.");
export { formatConfig, FormatConfigType };

const icon = new Enum(
    [
        "router",
        "star",
        "computer",
        "devices",
        "cellTower",
        "linkOff",
        "api",
        "hub",
        "settingsInputAntenna",
        "cloudSync",
        "deviceHub",
        "search",
    ],
    "enum.deviceType.icon."
);
export { icon };

const authenticationMethod = new Enum(
    [
        "none",
        "basic",
        "digest",
        // , "jwt",
        "x509",
    ],
    "enum.deviceType.authenticationMethod."
);
export { authenticationMethod };

const credentialsSource = new Enum(
    ["user", "secret", "both", "userIfSecretMissing"],
    "enum.deviceType.credentialsSource."
);
export { credentialsSource };

type CommunicationProcedureType =
    | "none"
    | "noneScep"
    | "noneVpn"
    | "routerOneConfig"
    | "router"
    | "routerDsa"
    | "edgeGateway"
    | "flexEdge"
    | "sgGateway"
    | "vpnContainerClient"
    | "edgeGatewayWithVpnContainerClient";
const communicationProcedure = new Enum(
    //commented out because not implemented yet
    [
        "none",
        "noneScep",
        "noneVpn",
        "routerOneConfig",
        "router",
        "routerDsa",
        /* "restApi",*/
        "edgeGateway",
        "flexEdge",
        "sgGateway",
        "vpnContainerClient",
        "edgeGatewayWithVpnContainerClient",
    ],
    "enum.deviceType.communicationProcedure."
);
export { communicationProcedure, CommunicationProcedureType };
