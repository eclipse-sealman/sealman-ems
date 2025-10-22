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
import { Alert, Box } from "@mui/material";
import { useSnackbar } from "@arteneo/forge";
import { useTranslation } from "react-i18next";
import Header from "~app/components/Layout/Header";
import Sidebar from "~app/components/Layout/Sidebar";
import { useSidebar } from "~app/contexts/Sidebar";
import Footer from "~app/components/Layout/Footer";
import GlobalLoader from "~app/components/Layout/GlobalLoader";
import { useConfiguration } from "~app/contexts/Configuration";
import { Outlet } from "react-router-dom";
import StatusHeader from "~app/components/Layout/StatusHeader";
import SsoCertificateNotification from "~app/components/Layout/SsoCertificateNotification";

const LayoutSidebar = () => {
    const { t } = useTranslation();
    const { maintenanceMode } = useConfiguration();
    const { snackbar } = useSnackbar();
    const { expanded } = useSidebar();

    return (
        <>
            <Header />
            <Box
                {...{
                    sx: {
                        position: "fixed",
                        top: "60px",
                        zIndex: (theme) => theme.zIndex.drawer + 10,
                        width: { xs: "100%", md: expanded ? "calc(100% - 260px)" : "calc(100% - 60px)" },
                        left: { xs: 0, md: expanded ? 260 : 60 },
                    },
                }}
            >
                <GlobalLoader />
                {snackbar}
            </Box>
            <Box
                {...{
                    sx: {
                        marginTop: "60px",
                        display: "grid",
                        gridTemplateColumns: {
                            xs: "1fr",
                            md: expanded ? "260px 1fr" : "60px 1fr",
                        },
                        minHeight: "calc(100vh - 60px)",
                    },
                }}
            >
                <Sidebar />
                <Box {...{ sx: { p: { xs: 1, md: 3 }, display: "flex", flexDirection: "column", overflow: "hidden" } }}>
                    {maintenanceMode && (
                        <Alert
                            {...{
                                severity: "error",
                                sx: {
                                    display: {
                                        xs: "flex",
                                        md: "none",
                                    },
                                    mt: {
                                        xs: -1,
                                        md: -3,
                                    },
                                    ml: {
                                        xs: -1,
                                        md: -3,
                                    },
                                    mr: {
                                        xs: -1,
                                        md: -3,
                                    },
                                    mb: {
                                        xs: 1,
                                        md: 3,
                                    },
                                    borderRadius: 0,
                                    border: 0,
                                    background: "red",
                                    color: "white",
                                    fontWeight: 700,
                                    justifyContent: "center",
                                    "& > .MuiAlert-icon": {
                                        color: "white",
                                        alignItems: "center",
                                    },
                                },
                            }}
                        >
                            {t("sidebar.maintenaceMode")}
                        </Alert>
                    )}
                    <StatusHeader />
                    <SsoCertificateNotification />
                    <Outlet />
                    <Box
                        {...{
                            sx: {
                                display: "flex",
                                justifyContent: "center",
                                alignItems: "flex-end",
                                flexGrow: 1,
                                mt: 3,
                            },
                        }}
                    >
                        <Footer />
                    </Box>
                </Box>
            </Box>
        </>
    );
};

export default LayoutSidebar;
