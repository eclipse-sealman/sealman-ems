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
import { Route, Routes, useLocation } from "react-router-dom";
import AccessTag from "~app/routes/AccessTag";
import Template from "~app/routes/Template";
import TemplateEdit from "~app/routes/TemplateEdit";
import TemplateCreateSelectDeviceType from "~app/routes/TemplateCreateSelectDeviceType";
import TemplateCreate from "~app/routes/TemplateCreate";
import TemplateVersionCreate from "~app/routes/TemplateVersionCreate";
import UserLoginAttempt from "~app/routes/UserLoginAttempt";
import DeviceFailedLoginAttempt from "~app/routes/DeviceFailedLoginAttempt";
import User from "~app/routes/User";
import Device from "~app/routes/Device";
import AuthenticationChangePasswordRequired from "~app/routes/AuthenticationChangePasswordRequired";
import AuthenticationTotpSecret from "~app/routes/AuthenticationTotpSecret";
import AuthenticationTotpRequired from "~app/routes/AuthenticationTotpRequired";
import AuthenticatedChangePassword from "~app/routes/AuthenticatedChangePassword";
import Login from "~app/routes/Login";
import SsoMicrosoftOidcLogin from "~app/routes/SsoMicrosoftOidcLogin";
import ConfigurationRoutes from "~app/routes/Configuration/Routes";
import MaintenanceRoutes from "~app/routes/Maintenance/Routes";
import RoleHomepageRedirect from "~app/security/RoleHomepageRedirect";
import RoleChangePasswordRequired from "~app/security/RoleChangePasswordRequired";
import RoleTotpSecret from "~app/security/RoleTotpSecret";
import RoleTotpRequired from "~app/security/RoleTotpRequired";
import DeviceCreate from "~app/routes/DeviceCreate";
import DeviceCreateSelectDeviceType from "~app/routes/DeviceCreateSelectDeviceType";
import DeviceEdit from "~app/routes/DeviceEdit";
import AuthenticatedNoAccess from "~app/routes/AuthenticatedNoAccess";
import Config from "~app/routes/Config";
import ConfigCreateSelectDeviceType from "~app/routes/ConfigCreateSelectDeviceType";
import ConfigCreateSelectFeature from "~app/routes/ConfigCreateSelectFeature";
import ConfigCreate from "~app/routes/ConfigCreate";
import ConfigEdit from "~app/routes/ConfigEdit";
import Firmware from "~app/routes/Firmware";
import FirmwareCreateSelectDeviceType from "~app/routes/FirmwareCreateSelectDeviceType";
import FirmwareCreateSelectFeature from "~app/routes/FirmwareCreateSelectFeature";
import FirmwareCreate from "~app/routes/FirmwareCreate";
import FirmwareEdit from "~app/routes/FirmwareEdit";
import TemplateVersionStagingEdit from "~app/routes/TemplateVersionStagingEdit";
import UserVpnConnectionDetails from "~app/routes/UserVpnConnectionDetails";
import VpnConnection from "~app/routes/VpnConnection";
import VpnConnectionOwned from "~app/routes/VpnConnectionOwned";
import VpnPermanentConnection from "~app/routes/VpnPermanentConnection";
import VpnLog from "~app/routes/VpnLog";
import AuditLogChange from "~app/routes/AuditLogChange";
import DiagnoseLog from "~app/routes/DiagnoseLog";
import ConfigLog from "~app/routes/ConfigLog";
import CommunicationLog from "~app/routes/CommunicationLog";
import DeviceEndpointDeviceDetails from "~app/routes/DeviceEndpointDeviceDetails";
import DeviceEndpointDeviceEdit from "~app/routes/DeviceEndpointDeviceEdit";
import DeviceAuthentication from "~app/routes/DeviceAuthentication";
import ImportFile from "~app/routes/ImportFile";
import ImportFileProcess from "~app/routes/ImportFileProcess";
import TriggerError404 from "~app/components/TriggerError404";
import LayoutSidebar from "~app/components/Layout/LayoutSidebar";
import RoleChecker from "~app/security/RoleChecker";
import Label from "~app/routes/Label";
import Status from "~app/routes/Status";
import DeviceCommand from "~app/routes/DeviceCommand";
import OpenSourceLicense from "~app/routes/OpenSourceLicense";
import UserCertificatesDetails from "~app/routes/UserCertificatesDetails";
import SecretLog from "~app/routes/SecretLog";
import DeviceSecret from "~app/routes/DeviceSecret";

const AppRoutes = () => {
    const location = useLocation();

    React.useLayoutEffect(() => {
        window.scrollTo(0, 0);
    }, [location.pathname]);

    return (
        <Routes>
            <Route index element={<RoleHomepageRedirect />} />
            <Route path="/authentication/login" element={<Login />} />
            <Route path="/authentication/sso/microsoftoidc/login" element={<SsoMicrosoftOidcLogin />} />
            <Route element={<RoleChecker authenticated />}>
                <Route path="/authenticated/noaccess" element={<AuthenticatedNoAccess />} />
            </Route>
            <Route element={<RoleChangePasswordRequired />}>
                <Route
                    path="/authentication/change/password/required"
                    element={<AuthenticationChangePasswordRequired />}
                />
            </Route>
            <Route element={<RoleTotpSecret />}>
                <Route path="/authentication/totp/secret" element={<AuthenticationTotpSecret />} />
            </Route>
            <Route element={<RoleTotpRequired />}>
                <Route path="/authentication/totp/required" element={<AuthenticationTotpRequired />} />
            </Route>
            <Route element={<LayoutSidebar />}>
                <Route element={<RoleChecker authenticated />}>
                    <Route path="/authenticated/change/password" element={<AuthenticatedChangePassword />} />
                </Route>
                <Route path="" element={<RoleChecker admin />}>
                    <Route path="/vpnconnection/*" element={<VpnConnection />} />
                    <Route path="/devicefailedloginattempt/*" element={<DeviceFailedLoginAttempt />} />
                    <Route path="/userloginattempt/*" element={<UserLoginAttempt />} />
                    <Route path="/user/*" element={<User />} />
                    <Route path="/deviceauthentication/*" element={<DeviceAuthentication />} />
                    <Route path="/maintenance/*" element={<MaintenanceRoutes />} />
                    <Route path="/accesstag/*" element={<AccessTag />} />
                    <Route path="/label/*" element={<Label />} />
                    <Route path="/status/*" element={<Status />} />
                    <Route path="/importfile/process/:id" element={<ImportFileProcess />} />
                    <Route path="/importfile/*" element={<ImportFile />} />
                    <Route path="/opensourcelicense/*" element={<OpenSourceLicense />} />
                    <Route path="/configuration/*" element={<ConfigurationRoutes />} />
                    <Route path="/secretlog/*" element={<SecretLog />} />
                </Route>
                <Route path="" element={<RoleChecker adminVpn />}>
                    <Route path="/vpnpermanentconnection/*" element={<VpnPermanentConnection />} />
                </Route>
                <Route path="" element={<RoleChecker adminScep vpn />}>
                    <Route path="/vpnlog/*" element={<VpnLog />} />
                </Route>
                <Route path="" element={<RoleChecker admin smartems vpn />}>
                    <Route path="/device/create/:deviceTypeId" element={<DeviceCreate />} />
                    <Route path="/device/create" element={<DeviceCreateSelectDeviceType />} />
                    <Route path="/device/edit/:id" element={<DeviceEdit />} />
                    <Route path="/device/*" element={<Device />} />
                    <Route path="/profile/certificates" element={<UserCertificatesDetails />} />
                    <Route path="/devicesecret/*" element={<DeviceSecret />} />
                </Route>
                <Route path="" element={<RoleChecker admin smartems />}>
                    <Route path="/devicecommand/*" element={<DeviceCommand />} />
                    <Route path="/communicationlog/*" element={<CommunicationLog />} />
                    <Route path="/configlog/*" element={<ConfigLog />} />
                    <Route path="/auditlogchange/*" element={<AuditLogChange />} />
                    <Route path="/diagnoselog/*" element={<DiagnoseLog />} />
                    <Route path="/template/edit/:id" element={<TemplateEdit />} />
                    <Route path="/template/*" element={<Template />} />
                    <Route path="/template/details/:templateId/version/create" element={<TemplateVersionCreate />} />
                    <Route path="/templateversion/edit/:id" element={<TemplateVersionStagingEdit />} />
                    <Route path="/template/create/:deviceTypeId" element={<TemplateCreate />} />
                    <Route path="/template/create" element={<TemplateCreateSelectDeviceType />} />
                    <Route path="/config/edit/:id" element={<ConfigEdit />} />
                    <Route path="/config/create/:deviceTypeId/:feature" element={<ConfigCreate />} />
                    <Route path="/config/create/:deviceTypeId" element={<ConfigCreateSelectFeature />} />
                    <Route path="/config/create" element={<ConfigCreateSelectDeviceType />} />
                    <Route path="/config/*" element={<Config />} />
                    <Route path="/firmware/edit/:id" element={<FirmwareEdit />} />
                    <Route path="/firmware/create/:deviceTypeId/:feature" element={<FirmwareCreate />} />
                    <Route path="/firmware/create/:deviceTypeId" element={<FirmwareCreateSelectFeature />} />
                    <Route path="/firmware/create" element={<FirmwareCreateSelectDeviceType />} />
                    <Route path="/firmware/*" element={<Firmware />} />
                </Route>
                <Route path="" element={<RoleChecker adminVpn vpn />}>
                    <Route path="/deviceendpointdevice/details/:id" element={<DeviceEndpointDeviceDetails />} />
                    <Route path="/deviceendpointdevice/edit/:id" element={<DeviceEndpointDeviceEdit />} />
                    <Route path="/vpnconnectionowned/*" element={<VpnConnectionOwned />} />
                    <Route path="/profile/vpn/details" element={<UserVpnConnectionDetails />} />
                </Route>
            </Route>
            <Route path="*" element={<TriggerError404 />} />
        </Routes>
    );
};

export default AppRoutes;
