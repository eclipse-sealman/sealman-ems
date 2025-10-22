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
import { BooleanColumn, getColumns } from "@arteneo/forge";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";
import ResultChangePassword from "~app/components/Table/actions/ResultChangePassword";
import ResultResetTotpSecret from "~app/entities/User/actions/ResultResetTotpSecret";
import ResultResetLoginAttempts from "~app/entities/User/actions/ResultResetLoginAttempts";
import ResultVpnDownloadConfig from "~app/components/Table/actions/ResultVpnDownloadConfig";
import CertificateColumn from "~app/components/Table/columns/CertificateColumn";
import Enable from "~app/entities/User/actions/Enable";
import Disable from "~app/entities/User/actions/Disable";
import EnabledColumn from "~app/entities/User/columns/EnabledColumn";
import CertificatesExpand from "~app/components/Table/actions/CertificatesExpand";
import UsernameColumn from "~app/entities/User/columns/UsernameColumn";

const columns = {
    username: <UsernameColumn />,
    certificate: <CertificateColumn disableSorting certificateCategory={"technicianVpn"} />,
    enabled: <EnabledColumn disableSorting />,
    disablePasswordExpire: <BooleanColumn />,
    tooManyFailedLoginAttempts: <BooleanColumn />,
    totpEnabled: <BooleanColumn />,
    roleAdmin: <BooleanColumn />,
    roleSmartems: <BooleanColumn />,
    roleVpn: <BooleanColumn />,
    roleVpnEndpointDevices: <BooleanColumn />,
    actions: (
        <BuilderActionsColumn
            {...{
                render: ({ editAction, deleteAction }) => (
                    <>
                        <CertificatesExpand entityPrefix="user" />
                        {editAction}
                        <ResultVpnDownloadConfig entityPrefix="user" />
                        <Enable />
                        <Disable />
                        <ResultChangePassword />
                        <ResultResetTotpSecret />
                        <ResultResetLoginAttempts />
                        {deleteAction}
                    </>
                ),
            }}
        />
    ),
};

export default getColumns(columns);
