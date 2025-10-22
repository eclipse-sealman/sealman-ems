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
import { Box } from "@mui/material";
import { useTranslation } from "react-i18next";
import VpnEndpointDeviceColumn from "~app/components/Table/columns/VpnEndpointDeviceColumn";
import { getIn } from "formik";
import VpnDeviceColumn from "~app/components/Table/columns/VpnDeviceColumn";
import VpnUserColumn from "~app/components/Table/columns/VpnUserColumn";

const VpnTargetColumn = (props: ColumnPathInterface) => {
    const { t } = useTranslation();

    if (typeof props?.columnName === "undefined") {
        throw new Error("VpnTargetColumn component: Missing required columnName prop");
    }

    if (typeof props?.result === "undefined") {
        throw new Error("VpnTargetColumn component: Missing required result prop");
    }

    const value = props?.path ? getIn(props?.result, props?.path) : props?.result;

    if (value?.endpointDevice) {
        return <VpnEndpointDeviceColumn {...props} />;
    }

    if (value?.device) {
        return <VpnDeviceColumn {...props} />;
    }

    if (value?.user) {
        return <VpnUserColumn {...props} />;
    }

    return <Box {...{ sx: { fontSize: 13 } }}>({t("label.unknown")})</Box>;
};

export default VpnTargetColumn;
export { ColumnPathInterface as VpnTargetColumnProps };
