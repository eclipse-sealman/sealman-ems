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
import VariablePreColumn, { VariablePreColumnProps } from "~app/components/Table/columns/VariablePreColumn";

type CertificateVariablePreColumnProps = VariablePreColumnProps & CertificateCategoryProps;

// Column is prepared to be used with chosen certificateCategory
const CertificateVariablePreColumn = ({
    result,
    columnName,
    path,
    certificateCategory = "deviceVpn",
    ...props
}: CertificateVariablePreColumnProps) => {
    if (typeof columnName === "undefined") {
        throw new Error("CertificateVariablePreColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("CertificateVariablePreColumn component: Missing required result prop");
    }

    const useableCertificate = getUsableCertificateByCategory(result, certificateCategory);
    if (!useableCertificate || !useableCertificate?.certificate) {
        return null;
    }

    return <VariablePreColumn {...{ result: useableCertificate.certificate, columnName, path, ...props }} />;
};

export default CertificateVariablePreColumn;
export { CertificateVariablePreColumnProps };
