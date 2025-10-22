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
import { Alert, Box, Drawer, Toolbar } from "@mui/material";
import { IconButton } from "@arteneo/forge";
import {
    ContentCopyOutlined,
    DownloadOutlined,
    HistoryOutlined,
    KeyboardDoubleArrowLeftOutlined,
    KeyboardDoubleArrowRightOutlined,
    KeyOutlined,
    LabelOutlined,
    LinkOutlined,
    LockOutlined,
    MemoryOutlined,
    PersonOutlined,
    RouterOutlined,
    SearchOffOutlined,
    SettingsOutlined,
} from "@mui/icons-material";
import { useTranslation } from "react-i18next";
import CollapsedSidebarNode from "~app/components/Layout/CollapsedSidebarNode";
import SidebarNode, { SidebarNodeProps } from "~app/components/Layout/SidebarNode";
import { useSidebar } from "~app/contexts/Sidebar";
import { useConfiguration } from "~app/contexts/Configuration";

const Sidebar = () => {
    const { t } = useTranslation();
    const { maintenanceMode } = useConfiguration();
    const { expanded, setExpanded, mobileOpen, setMobileOpen } = useSidebar();

    const nodes: SidebarNodeProps[] = [
        {
            adminVpn: true,
            label: "sidebar.vpnConnection",
            to: "/vpnconnection/list",
            active: "/vpnconnection",
            icon: <LinkOutlined />,
        },
        {
            vpn: true,
            label: "sidebar.vpnConnection",
            to: "/vpnconnectionowned/list",
            active: "/vpnconnectionowned",
            icon: <LinkOutlined />,
        },
        {
            adminVpn: true,
            label: "sidebar.vpnPermanentConnection",
            to: "/vpnpermanentconnection/list",
            active: "/vpnpermanentconnection",
            icon: <SearchOffOutlined />,
        },
        {
            admin: true,
            smartems: true,
            vpn: true,
            label: "sidebar.device",
            to: "/device/list",
            active: "/device",
            icon: <RouterOutlined />,
        },
        {
            admin: true,
            smartems: true,
            label: "sidebar.template",
            to: "/template/list",
            active: "/template",
            icon: <ContentCopyOutlined />,
        },
        {
            admin: true,
            smartems: true,
            label: "sidebar.config",
            to: "/config/list",
            active: "/config",
            icon: <SettingsOutlined />,
        },
        {
            admin: true,
            smartems: true,
            label: "sidebar.firmware",
            to: "/firmware/list",
            active: "/firmware",
            icon: <MemoryOutlined />,
        },
        {
            admin: true,
            smartems: true,
            vpn: true,
            label: "sidebar.logs.expandable",
            icon: <HistoryOutlined />,
            children: [
                {
                    admin: true,
                    label: "sidebar.logs.userLoginAttempt",
                    to: "/userloginattempt/list",
                    active: "/userloginattempt",
                },
                {
                    admin: true,
                    label: "sidebar.logs.deviceFailedLoginAttempt",
                    to: "/devicefailedloginattempt/list",
                    active: "/devicefailedloginattempt",
                },
                {
                    admin: true,
                    label: "sidebar.logs.secret",
                    to: "/secretlog/list",
                    active: "/secretlog",
                },
                {
                    admin: true,
                    smartems: true,
                    label: "sidebar.logs.communication",
                    to: "/communicationlog/list",
                    active: "/communicationlog",
                },
                {
                    admin: true,
                    smartems: true,
                    label: "sidebar.logs.deviceCommand",
                    to: "/devicecommand/list",
                    active: "/devicecommand",
                },
                {
                    admin: true,
                    smartems: true,
                    label: "sidebar.logs.config",
                    to: "/configlog/list",
                    active: "/configlog",
                },
                {
                    admin: true,
                    smartems: true,
                    label: "sidebar.logs.diagnose",
                    to: "/diagnoselog/list",
                    active: "/diagnoselog",
                },
                { adminScep: true, vpn: true, label: "sidebar.logs.vpn", to: "/vpnlog/list", active: "/vpnlog" },
                { admin: true, label: "sidebar.logs.audit", to: "/auditlogchange/list", active: "/auditlogchange" },
            ],
        },
        { admin: true, label: "sidebar.user", to: "/user/list", active: "/user", icon: <PersonOutlined /> },
        {
            admin: true,
            label: "sidebar.deviceAuthentication",
            to: "/deviceauthentication/list",
            active: "/deviceauthentication",
            icon: <KeyOutlined />,
        },
        {
            admin: true,
            label: "sidebar.accessTag",
            to: "/accesstag/list",
            active: "/accesstag",
            icon: <LockOutlined />,
        },
        {
            admin: true,
            label: "sidebar.label",
            to: "/label/list",
            active: "/label",
            icon: <LabelOutlined />,
        },
        {
            admin: true,
            label: "sidebar.importFile.expandable",
            icon: <DownloadOutlined />,
            children: [
                {
                    admin: true,
                    label: "sidebar.importFile.create",
                    to: "/importfile/create",
                    active: "/importfile/create",
                },
                {
                    admin: true,
                    label: "sidebar.importFile.list",
                    to: "/importfile/list",
                    active: "/importfile/list",
                },
            ],
        },
    ];

    return (
        <>
            <Drawer
                {...{
                    variant: "permanent",
                    sx: {
                        width: expanded ? 260 : 60,
                        flexShrink: 0,
                        display: { xs: "none", md: "block" },
                        "& .MuiDrawer-paper": {
                            width: expanded ? 260 : 60,
                            borderRight: 0,
                        },
                    },
                }}
            >
                <Toolbar />
                {maintenanceMode && (
                    <Alert
                        {...{
                            severity: "error",
                            sx: {
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
                {nodes.map((node, key) =>
                    expanded ? <SidebarNode key={key} {...node} /> : <CollapsedSidebarNode key={key} {...node} />
                )}
                <Box
                    {...{
                        sx: { display: "flex", py: 1, justifyContent: "center", alignItems: "flex-end", flexGrow: 1 },
                    }}
                >
                    <IconButton
                        {...{
                            // Force rerender to hide tooltip after click
                            key: expanded ? 1 : 2,
                            tooltip: "sidebar.action." + (expanded ? "collapse" : "expand"),
                            icon: expanded ? <KeyboardDoubleArrowLeftOutlined /> : <KeyboardDoubleArrowRightOutlined />,
                            color: "primary",
                            variant: "outlined",
                            sx: {
                                borderWidth: 1,
                                borderStyle: "solid",
                                borderColor: "#e9e9e9",
                            },
                            onClick: () => setExpanded(!expanded),
                        }}
                    />
                </Box>
            </Drawer>
            <Drawer
                {...{
                    variant: "temporary",
                    open: mobileOpen,
                    onClose: () => setMobileOpen(false),
                    ModalProps: {
                        keepMounted: true,
                    },
                    sx: {
                        width: 260,
                        display: { xs: "block", md: "none" },
                        "& .MuiDrawer-paper": {
                            width: 260,
                            borderRight: 0,
                        },
                    },
                }}
            >
                <Toolbar />
                {nodes.map((node, key) => (
                    <SidebarNode key={key} {...node} />
                ))}
            </Drawer>
        </>
    );
};

export default Sidebar;
