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

import React from "react";
import { BooleanColumn, BooleanColumnProps } from "@arteneo/forge";
import { CertificateCategoryProps } from "~app/definitions/CertificateTypeDefinitions";
import { getUsableCertificateByCategory } from "~app/utilities/certificateType";

type CertificateBooleanColumnProps = BooleanColumnProps & CertificateCategoryProps;

// Column is prepared to be used with chosen certificateCategory
const CertificateBooleanColumn = ({
    result,
    columnName,
    path,
    certificateCategory = "deviceVpn",
    ...props
}: CertificateBooleanColumnProps) => {
    if (typeof columnName === "undefined") {
        throw new Error("CertificateBooleanColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("CertificateBooleanColumn component: Missing required result prop");
    }

    const useableCertificate = getUsableCertificateByCategory(result, certificateCategory);
    if (!useableCertificate || !useableCertificate?.certificate) {
        return null;
    }

    return <BooleanColumn {...{ result: useableCertificate.certificate, columnName, path, ...props }} />;
};

export default CertificateBooleanColumn;
export { CertificateBooleanColumnProps };
