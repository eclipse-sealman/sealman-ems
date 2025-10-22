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
import { CertificateCategoryProps } from "~app/definitions/CertificateTypeDefinitions";
import { getUsableCertificateByCategory } from "~app/utilities/certificateType";
import DateTimeSecondsColumn, { DateTimeSecondsColumnProps } from "~app/components/Table/columns/DateTimeSecondsColumn";

type CertificateDateTimeSecondsColumnProps = DateTimeSecondsColumnProps & CertificateCategoryProps;

// Column is prepared to be used with chosen certificateCategory
const CertificateDateTimeSecondsColumn = ({
    result,
    columnName,
    path,
    certificateCategory = "deviceVpn",
    ...props
}: CertificateDateTimeSecondsColumnProps) => {
    if (typeof columnName === "undefined") {
        throw new Error("CertificateDateTimeSecondsColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("CertificateDateTimeSecondsColumn component: Missing required result prop");
    }

    const useableCertificate = getUsableCertificateByCategory(result, certificateCategory);
    if (!useableCertificate || !useableCertificate?.certificate) {
        return null;
    }

    return <DateTimeSecondsColumn {...{ result: useableCertificate.certificate, columnName, path, ...props }} />;
};

export default CertificateDateTimeSecondsColumn;
export { CertificateDateTimeSecondsColumnProps };
