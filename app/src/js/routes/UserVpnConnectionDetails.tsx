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
import { useHandleCatch, useLoader } from "@arteneo/forge";
import { Alert, Box } from "@mui/material";
import { SettingsInputHdmi } from "@mui/icons-material";
import { useTranslation } from "react-i18next";
import { UserInterface } from "~app/entities/User/definitions";
import Pre from "~app/components/Common/Pre";
import ButtonVpnDownloadConfig from "~app/components/Common/ButtonVpnDownloadConfig";
import DisplaySurface from "~app/components/Common/DisplaySurface";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";
import ButtonCopyToClipboard from "~app/components/Common/ButtonCopyToClipboard";
import CertificateXsColumn from "~app/components/Table/columns/CertificateXsColumn";
import Display from "~app/components/Display/Display";
import { getUsableCertificateByCategory } from "~app/utilities/certificateType";
import CertificateVariablePreColumn from "~app/components/Table/columns/CertificateVariablePreColumn";
import Surface from "~app/components/Common/Surface";

const UserVpnConnectionDetails = () => {
    const { t } = useTranslation();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [user, setUser] = React.useState<undefined | UserInterface>(undefined);

    React.useEffect(() => load(), []);

    const load = () => {
        showLoader();

        axios
            .get("/vpn/certificates")
            .then((response) => {
                setUser(response.data);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });
    };

    if (typeof user === "undefined") {
        return null;
    }

    const useableCertificate = getUsableCertificateByCategory(user, "technicianVpn");
    if (!useableCertificate || !useableCertificate?.certificate) {
        return (
            <>
                <SurfaceTitle {...{ title: "userVpnConnectionDetails.title", icon: <SettingsInputHdmi /> }} />
                <Surface>
                    <Alert severity="warning">{t("userVpnConnectionDetails.technicianVpnNotAvailable")}</Alert>
                </Surface>
            </>
        );
    }

    const hasCertificate = useableCertificate?.certificate?.hasCertificate ?? false;
    const openVpnConnectString = "openvpn --config " + useableCertificate?.certificate?.certificateSubject + ".ovpn";

    const rows = {
        certificateStatus: <CertificateXsColumn certificateCategory="technicianVpn" />,
        certificateSubject: <CertificateVariablePreColumn certificateCategory="technicianVpn" />,
        certificate: <CertificateVariablePreColumn certificateCategory="technicianVpn" />,
        privateKey: <CertificateVariablePreColumn certificateCategory="technicianVpn" />,
        certificateCa: <CertificateVariablePreColumn certificateCategory="technicianVpn" />,
    };

    return (
        <>
            <SurfaceTitle {...{ title: "userVpnConnectionDetails.title", icon: <SettingsInputHdmi /> }} />
            <Box
                {...{
                    sx: {
                        display: "grid",
                        alignItems: "flex-start",
                        gridTemplateColumns: {
                            xs: "minmax(0, 1fr)",
                            lg: "repeat(2, minmax(0,1fr))",
                        },
                        gap: { xs: 2, lg: 4 },
                        mb: 2,
                    },
                }}
            >
                <DisplaySurface
                    {...{
                        title: "userVpnConnectionDetails.howToConnect",
                    }}
                >
                    {hasCertificate ? (
                        <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 2 } }}>
                            <Box {...{ sx: { display: "flex", gap: 2 } }}>
                                <Pre {...{ sx: { flexGrow: 1 }, content: openVpnConnectString }} />
                                <ButtonCopyToClipboard {...{ text: openVpnConnectString }} />
                            </Box>
                            <ButtonVpnDownloadConfig
                                {...{
                                    fullWidth: true,
                                    size: "large",
                                    endpoint: "/vpn/client/config",
                                    deny: user?.deny,
                                }}
                            />
                        </Box>
                    ) : (
                        <Alert severity="warning">{t("userVpnConnectionDetails.missingCertificate")}</Alert>
                    )}
                </DisplaySurface>
                <DisplaySurface
                    {...{
                        title: "userVpnConnectionDetails.certificate",
                    }}
                >
                    {hasCertificate ? (
                        <Display {...{ result: user, rows }} />
                    ) : (
                        <Alert severity="warning">{t("userVpnConnectionDetails.missingCertificate")}</Alert>
                    )}
                </DisplaySurface>
            </Box>
        </>
    );
};

export default UserVpnConnectionDetails;
