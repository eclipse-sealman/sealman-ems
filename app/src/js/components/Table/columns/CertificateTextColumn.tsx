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
import { getIn } from "formik";
import { ColumnPathInterface } from "@arteneo/forge";
import { CertificateCategoryProps } from "~app/definitions/CertificateTypeDefinitions";
import { getUsableCertificateByCategory } from "~app/utilities/certificateType";

type CertificateTextColumnProps = ColumnPathInterface & CertificateCategoryProps;

// Column is prepared to be used with chosen certificateCategory
const CertificateTextColumn = ({
    result,
    columnName,
    path,
    certificateCategory = "deviceVpn",
}: CertificateTextColumnProps) => {
    if (typeof columnName === "undefined") {
        throw new Error("CertificateTextColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("CertificateTextColumn component: Missing required result prop");
    }

    const useableCertificate = getUsableCertificateByCategory(result, certificateCategory);
    if (!useableCertificate || !useableCertificate?.certificate) {
        return null;
    }

    const value = getIn(useableCertificate?.certificate, path ?? columnName);
    if (!value) {
        return null;
    }

    return <>{value}</>;
};

export default CertificateTextColumn;
export { CertificateTextColumnProps };
