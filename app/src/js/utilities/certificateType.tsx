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

import { CertificateCategoryType } from "~app/entities/CertificateType/enums";
import { UseableCertificateEntityInterface } from "~app/entities/Common/definitions";

export const getUsableCertificateByCategory = (
    usableCertificateEntity: UseableCertificateEntityInterface,
    certificateCategory: CertificateCategoryType
) => {
    if (!Array.isArray(usableCertificateEntity.useableCertificates)) {
        return undefined;
    }

    return usableCertificateEntity.useableCertificates.find(
        (useableCertificate) => useableCertificate?.certificateType?.certificateCategory == certificateCategory
    );
};
