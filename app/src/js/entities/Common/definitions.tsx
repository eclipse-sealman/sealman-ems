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

import { DenyInterface, ResultInterface } from "@arteneo/forge";
import EntityDenyInterface from "~app/definitions/EntityDenyInterface";
import EntityInterface from "~app/definitions/EntityInterface";
import { CertificateBehaviorType, CertificateCategoryType } from "~app/entities/CertificateType/enums";

interface CertificateInterface extends ResultInterface {
    certificateSubject?: string;
    certificate?: string;
    privateKey?: string;
    certificateCa?: string;
    certificateCaSubject?: string;
    certificateValidTo?: string;
    certificateGenerated?: boolean;
    hasCertificate?: boolean;
    isCertificateExpired?: boolean;
}

interface UseableCertificateEntityInterface extends EntityDenyInterface {
    useableCertificates?: UseableCertificateInterface[];
}

interface UseableCertificateInterface {
    certificateType: CertificateTypeInterface;
    certificate: CertificateInterface;
    deny?: DenyInterface;
    generateCertificate?: boolean;
    revokeCertificate?: boolean;
}

interface CertificateTypeInterface extends EntityInterface {
    certificateCategory: CertificateCategoryType;
    enabledBehaviour: CertificateBehaviorType;
    disabledBehaviour: CertificateBehaviorType;
    pkiEnabled?: boolean;
}

interface VpnInterface {
    vpnIpSortable?: number;
    vpnIp?: string;
    vpnConnected?: boolean;
}

type CertificateAutomaticBehaviorStateType =
    | "none"
    | "onDemandEnabled"
    | "onDemandDisabled"
    | "autoEnabled"
    | "autoDisabled";

interface CertificateAutomaticBehavior {
    certificateType: CertificateTypeInterface;
    generateCertificate: CertificateAutomaticBehaviorStateType;
    revokeCertificate: CertificateAutomaticBehaviorStateType;
}

interface CertificateAutomaticBehaviorExtended extends CertificateAutomaticBehavior {
    canGenerateCertificate: boolean;
    canRevokeCertificate: boolean;
}

export {
    CertificateInterface,
    UseableCertificateInterface,
    UseableCertificateEntityInterface,
    CertificateTypeInterface,
    VpnInterface,
    CertificateAutomaticBehavior,
    CertificateAutomaticBehaviorExtended,
    CertificateAutomaticBehaviorStateType,
};
