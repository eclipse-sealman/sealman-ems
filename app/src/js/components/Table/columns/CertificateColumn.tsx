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
import CertificateStatus, { CertificateStatusProps } from "~app/components/Common/CertificateStatus";

type CertificateColumnProps = CertificateStatusProps & ColumnPathInterface;

const CertificateColumn = ({ result, columnName, path, ...props }: CertificateColumnProps) => {
    if (typeof columnName === "undefined") {
        throw new Error("CertificateColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("CertificateColumn component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;
    if (!value) {
        return null;
    }

    return <CertificateStatus {...{ usableCertificateEntity: value, ...props }} />;
};

export default CertificateColumn;
export { CertificateColumnProps };
