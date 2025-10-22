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
import Display from "~app/components/Display/Display";
import BooleanXsColumn from "~app/components/Table/columns/BooleanXsColumn";
import { useConfiguration } from "~app/contexts/Configuration";

const FeatureStatus = () => {
    const { isScepAvailable, isVpnAvailable } = useConfiguration();

    const rows = {
        featureScep: <BooleanXsColumn path={"scep"} />,
        featureVpn: <BooleanXsColumn path={"vpn"} />,
    };

    return (
        <>
            <Display
                {...{
                    result: {
                        scep: isScepAvailable,
                        vpn: isVpnAvailable,
                    },
                    rows,
                }}
            />
        </>
    );
};

export default FeatureStatus;
