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
import { Tooltip } from "@mui/material";
import { useTranslation } from "react-i18next";
import { WarningOutlined } from "@mui/icons-material";
import { formatBytes } from "~app/utilities/format";
import axios from "axios";
import { AXIOS_CANCELLED_UNMOUNTED, useHandleCatch } from "@arteneo/forge";

interface DiskAlertInterface {
    alert: boolean;
    usage: number;
    total: number;
}

const HeaderDiskAlert = () => {
    const { t } = useTranslation();
    const handleCatch = useHandleCatch();

    const [diskUsage, setDiskUsage] = React.useState<DiskAlertInterface | undefined>(undefined);

    React.useEffect(() => load(), []);

    const load = () => {
        const axiosSource = axios.CancelToken.source();

        axios
            .get("/status/disk")
            .then((response) => {
                setDiskUsage(response.data);
            })
            .catch((error) => {
                handleCatch(error);
            });

        return () => {
            axiosSource.cancel(AXIOS_CANCELLED_UNMOUNTED);
        };
    };

    if (!diskUsage || !diskUsage.alert) {
        return null;
    }

    return (
        <Tooltip
            title={t("header.diskUsage.alert", {
                percent: 100 - Math.round((diskUsage.usage * 100) / diskUsage.total),
                usage: formatBytes(diskUsage.usage),
                total: formatBytes(diskUsage.total),
            })}
        >
            <WarningOutlined {...{ color: "error" }} />
        </Tooltip>
    );
};

export default HeaderDiskAlert;
