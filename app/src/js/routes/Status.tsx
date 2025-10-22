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

import { PieChart, Subtitles } from "@mui/icons-material";
import { Box } from "@mui/system";
import React from "react";
import Surface from "~app/components/Common/Surface";
import FeatureStatus from "~app/components/Status/FeatureStatus";
import SystemStatus from "~app/components/Status/SystemStatus";

interface StatusProps {
    refreshCounter: number;
    increaseRefreshCounter: () => void;
}

const Status = () => {
    const [refreshCounter, setRefreshCounter] = React.useState(0);
    const increaseRefreshCounter = () => {
        setRefreshCounter((value) => value + 1);
    };

    return (
        <>
            <Box
                {...{
                    sx: {
                        display: "grid",
                        alignItems: "flex-start",
                        gridTemplateColumns: { xs: "minmax(0,1fr)", lg: "repeat(2, minmax(0,1fr))" },
                        gap: { xs: 2, lg: 4 },
                        mb: 2,
                    },
                }}
            >
                <Surface
                    {...{
                        title: "route.title.status.serverStatus",
                        icon: <PieChart />,
                    }}
                >
                    <SystemStatus {...{ refreshCounter, increaseRefreshCounter }} />
                </Surface>
                <Surface
                    {...{
                        title: "route.title.status.featureStatus",
                        icon: <Subtitles />,
                    }}
                >
                    <FeatureStatus />
                </Surface>
            </Box>
        </>
    );
};

export default Status;
export { StatusProps };
