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
import axios from "axios";
import { AXIOS_CANCELLED_UNMOUNTED, useHandleCatch } from "@arteneo/forge";
import CircularLoader from "~app/components/Layout/CircularLoader";
import Display from "~app/components/Display/Display";
import getRows from "~app/components/Status/SystemStatusRows";
import { StatusProps } from "~app/routes/Status";

interface SystemStatusInterface {
    appVersion?: string;
    systemTime?: string;
    cpu?: string;
    ram?: string;
    filesystem?: string;
    databaseSize?: string;
}

const SystemStatus = ({ refreshCounter }: StatusProps) => {
    const rows = getRows();
    const handleCatch = useHandleCatch();
    const [loading, setLoading] = React.useState<boolean>(false);

    const [systemStatus, setSystemStatus] = React.useState<SystemStatusInterface | undefined>(undefined);

    React.useEffect(() => load(), [refreshCounter]);

    const load = () => {
        setLoading(true);
        const axiosSource = axios.CancelToken.source();

        axios
            .get("/status/system")
            .then((response) => {
                setSystemStatus(response.data);
                setLoading(false);
            })
            .catch((error) => {
                setLoading(false);
                handleCatch(error);
            });

        return () => {
            axiosSource.cancel(AXIOS_CANCELLED_UNMOUNTED);
        };
    };

    if (loading) {
        return <CircularLoader />;
    }

    if (!systemStatus) {
        return null;
    }

    return (
        <Display
            {...{
                result: systemStatus,
                rows,
            }}
        />
    );
};

export default SystemStatus;
