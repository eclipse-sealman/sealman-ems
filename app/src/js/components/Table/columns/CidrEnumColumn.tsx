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
import { ColumnPathInterface } from "@arteneo/forge";
import { getIn } from "formik";
import { useTranslation } from "react-i18next";
import { cidr } from "~app/enums/Cidr";

const CidrEnumColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("CidrEnumColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("CidrEnumColumn component: Missing required result prop");
    }

    const { t } = useTranslation();

    const value = getIn(result, path ? path : columnName);

    if (!value) {
        return null;
    }

    if (cidr.isValid(value + "")) {
        return <>{value && t(cidr.getLabel(value + ""))}</>;
    } else {
        return <>/{value}</>;
    }
};

export default CidrEnumColumn;
export { ColumnPathInterface as CidrEnumColumnProps };
