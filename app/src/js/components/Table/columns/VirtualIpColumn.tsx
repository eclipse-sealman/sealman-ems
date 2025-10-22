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

interface VirtualIpColumnProps extends ColumnPathInterface {
    virtualIpHostPartPath: string;
    virtualIpPath?: string;
}

const VirtualIpColumn = ({ result, columnName, path, virtualIpHostPartPath, virtualIpPath }: VirtualIpColumnProps) => {
    const { t } = useTranslation();

    if (typeof columnName === "undefined") {
        throw new Error("VirtualIpColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("VirtualIpColumn component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;
    if (!value) {
        return null;
    }

    const virtualIp = getIn(value, virtualIpPath ? virtualIpPath : columnName);
    if (virtualIp) {
        return <>{virtualIp}</>;
    }

    const virtualIpHostPart = getIn(value, virtualIpHostPartPath);
    if (!virtualIpHostPart) {
        return null;
    }

    return <>{t("virtualIpHostPart.option", { number: virtualIpHostPart })}</>;
};

export default VirtualIpColumn;
export { VirtualIpColumnProps };
