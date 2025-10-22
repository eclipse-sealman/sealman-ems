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

import { DenyInterface } from "@arteneo/forge";
import { UseableCertificateEntityInterface, VpnInterface } from "~app/entities/Common/definitions";

interface UserInterface extends VpnInterface, UseableCertificateEntityInterface {
    id: number;
    representation: string;
    roleAdmin: boolean;
    roleVpn: boolean;
    roleSmartems: boolean;
    deny?: DenyInterface;
}

export { UserInterface };
