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

import { Alert } from "@mui/material";
import { Box } from "@mui/system";
import React from "react";
import { useTranslation } from "react-i18next";
import { useConfiguration } from "~app/contexts/Configuration";

const StatusHeader = () => {
    const { t } = useTranslation();
    const { maintenanceMode } = useConfiguration();

    let alert = null;

    if (maintenanceMode) {
        alert = <Alert severity="error">{t("header.status.maintenanceModeEnabled")}</Alert>;
    }

    if (alert == null) {
        return null;
    }

    return <Box {...{ sx: { mb: { xs: 1, md: 2 } } }}>{alert}</Box>;
};

export default StatusHeader;
