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
import { Route, Routes as RouterRoutes } from "react-router-dom";
import Dashboard from "~app/routes/Configuration/Dashboard";
import General from "~app/routes/Configuration/General";
import Logs from "~app/routes/Configuration/Logs";
import Radius from "~app/routes/Configuration/Radius";
import Totp from "~app/routes/Configuration/Totp";
import Sso from "~app/routes/Configuration/Sso";
import Vpn from "~app/routes/Configuration/Vpn";
import Documentation from "~app/routes/Configuration/Documentation";
import DeviceType from "~app/routes/Configuration/DeviceType";
import DeviceTypeCreateSelectCommunicationProcedure from "~app/routes/Configuration/DeviceTypeCreateSelectCommunicationProcedure";
import DeviceTypeCreate from "~app/routes/Configuration/DeviceTypeCreate";
import DeviceTypeEdit from "~app/routes/Configuration/DeviceTypeEdit";
import DeviceTypeLimitedEdit from "~app/routes/Configuration/DeviceTypeLimitedEdit";
import DeviceTypeDetails from "~app/routes/Configuration/DeviceTypeDetails";
import TriggerError404 from "~app/components/TriggerError404";
import RoleChecker from "~app/security/RoleChecker";
import CertificateType from "~app/routes/Configuration/CertificateType";
import DeviceTypeSecret from "~app/routes/Configuration/DeviceTypeSecret";
import DeviceTypeSecretCreate from "~app/routes/Configuration/DeviceTypeSecretCreate";

const Routes = () => {
    return (
        <RouterRoutes>
            <Route
                {...{
                    path: "/dashboard",
                    element: <Dashboard />,
                }}
            />
            <Route
                {...{
                    path: "/general",
                    element: <General />,
                }}
            />
            <Route path="/devicetypesecret/:deviceTypeId/create" element={<DeviceTypeSecretCreate />} />
            <Route
                {...{
                    path: "/devicetypesecret/:deviceTypeId/*",
                    element: <DeviceTypeSecret />,
                }}
            />
            <Route path="/devicetype/create" element={<DeviceTypeCreateSelectCommunicationProcedure />} />
            <Route path="/devicetype/create/:communicationProcedure" element={<DeviceTypeCreate />} />
            <Route path="/devicetype/edit/:id" element={<DeviceTypeEdit />} />
            <Route path="/devicetype/limitededit/:id" element={<DeviceTypeLimitedEdit />} />
            <Route path="/devicetype/details/:id" element={<DeviceTypeDetails />} />
            <Route
                {...{
                    path: "/devicetype/*",
                    element: <DeviceType />,
                }}
            />
            <Route
                {...{
                    path: "/certificatetype/*",
                    element: <CertificateType />,
                }}
            />
            <Route
                {...{
                    path: "/logs",
                    element: <Logs />,
                }}
            />
            <Route
                {...{
                    path: "/radius",
                    element: <Radius />,
                }}
            />
            <Route
                {...{
                    path: "/totp",
                    element: <Totp />,
                }}
            />
            <Route
                {...{
                    path: "/sso",
                    element: <Sso />,
                }}
            />
            <Route path="" element={<RoleChecker adminVpn />}>
                <Route
                    {...{
                        path: "/vpn",
                        element: <Vpn />,
                    }}
                />
            </Route>
            <Route
                {...{
                    path: "/documentation",
                    element: <Documentation />,
                }}
            />
            <Route path="*" element={<TriggerError404 />} />
        </RouterRoutes>
    );
};

export default Routes;
