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
import { getFeatureName } from "~app/entities/Config/utilities";

const FeatureNameConfigColumn = ({ path, result, columnName }: ColumnPathInterface) => {
    const { t } = useTranslation();

    if (typeof columnName === "undefined") {
        throw new Error("FeatureNameConfigColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("FeatureNameConfigColumn component: Missing required result prop");
    }

    const value = path ? getIn(result, path) : result;

    const deviceType = value?.device?.deviceType;
    const feature = value?.feature;
    if (typeof deviceType === "undefined") {
        return <>{t("label.config" + feature)}</>;
    }

    const featureName = getFeatureName(deviceType, feature);

    return <>{featureName}</>;
};

export default FeatureNameConfigColumn;
export { ColumnPathInterface as FeatureNameConfigColumnProps };
