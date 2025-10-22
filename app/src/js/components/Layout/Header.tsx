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
import { AppBar, Box, IconButton, Toolbar } from "@mui/material";
import { MenuOutlined } from "@mui/icons-material";
import { useTranslation } from "react-i18next";
import HeaderVpnConnection from "~app/components/Layout/HeaderVpnConnection";
import HeaderProfile from "~app/components/Layout/HeaderProfile";
import HeaderSession from "~app/components/Layout/HeaderSession";
import HeaderDiskAlert from "~app/components/Layout/HeaderDiskAlert";
import { useSidebar } from "~app/contexts/Sidebar";
import { useUser } from "~app/contexts/User";
import logoSidebar from "~images/logo-sidebar.svg";

const Header = () => {
    const { isAccessGranted } = useUser();
    const { t } = useTranslation();
    const { mobileOpen, setMobileOpen } = useSidebar();

    return (
        <AppBar
            {...{
                position: "fixed",
                color: "default",
                elevation: 0,
                sx: {
                    zIndex: (theme) => theme.zIndex.drawer + 20,
                    height: 60,
                    backgroundColor: "white",
                    border: 0,
                    borderBottomWidth: 1,
                    borderBottomStyle: "solid",
                    borderBottomColor: "#e9e9e9",
                },
            }}
        >
            <Toolbar
                {...{
                    disableGutters: true,
                    sx: {
                        display: "grid",
                        gridTemplateColumns: { xs: "45px 1fr", md: "236px 1fr" },
                        gap: { xs: 1, md: 3 },
                        px: { xs: 1, md: 3 },
                        minHeight: 59,
                        "@media (min-width: 0px)": {
                            "@media (orientation: landscape)": {
                                minHeight: 59,
                            },
                        },
                        "@media (min-width: 600px)": {
                            minHeight: 59,
                        },
                    },
                }}
            >
                <Box
                    {...{
                        sx: {
                            py: 1,
                            pr: { xs: 1, md: 3 },
                            display: { xs: "none", md: "flex" },
                            justifyContent: "center",
                            alignItems: "center",
                            height: "100%",
                            overflow: "hidden",
                        },
                    }}
                >
                    <Box
                        {...{
                            component: "img",
                            sx: { maxHeight: "100%", maxWidth: "100%", objectFit: "contain" },
                            src: logoSidebar,
                            alt: t("alt.logo"),
                        }}
                    />
                </Box>
                <Box
                    {...{
                        sx: { display: { md: "none" }, justifyContent: "center", alignItems: "center" },
                    }}
                >
                    <IconButton
                        {...{
                            onClick: () => setMobileOpen(!mobileOpen),
                        }}
                    >
                        <MenuOutlined />
                    </IconButton>
                </Box>
                <Box
                    {...{
                        sx: {
                            display: "flex",
                            justifyContent: { xs: "flex-end", lg: "space-between" },
                            alignItems: "center",
                        },
                    }}
                >
                    <Box
                        {...{
                            // TODO Arek Adjust this on mobile when HeaderVpnConnection will have all cases implemented
                            sx: { display: { xs: "none", lg: "block" } },
                        }}
                    >
                        {isAccessGranted({ adminVpn: true, vpn: true }) && <HeaderVpnConnection />}
                    </Box>
                    <Box {...{ sx: { display: "flex", alignItems: "center", gap: 2 } }}>
                        <HeaderSession />
                        {isAccessGranted({ admin: true }) && <HeaderDiskAlert />}
                        <HeaderProfile />
                    </Box>
                </Box>
            </Toolbar>
        </AppBar>
    );
};

export default Header;
