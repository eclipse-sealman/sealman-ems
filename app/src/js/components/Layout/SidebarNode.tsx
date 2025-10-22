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
import { useTranslation } from "react-i18next";
import { Collapse, List, ListItem, ListItemButton, ListItemIcon, ListItemText } from "@mui/material";
import { ExpandLessOutlined, ExpandMoreOutlined } from "@mui/icons-material";
import { Link, useLocation } from "react-router-dom";
import SidebarChildNode, { SidebarChildNodeProps } from "~app/components/Layout/SidebarChildNode";
import { isLocationActive } from "~app/utilities/location";
import { useSidebar } from "~app/contexts/Sidebar";
import { RoleCheckerProps, useUser } from "~app/contexts/User";

interface SidebarNodeProps extends RoleCheckerProps {
    label: string;
    icon: React.ReactNode;
    to?: string;
    active?: string;
    children?: SidebarChildNodeProps[];
}

const SidebarNode = ({ label, to, active, icon, children = [], ...roleCheckerProps }: SidebarNodeProps) => {
    const { t } = useTranslation();
    const location = useLocation();
    const { setMobileOpen } = useSidebar();
    const { isAccessGranted } = useUser();

    const hasTo = typeof to !== "undefined" && typeof active !== "undefined";

    if (hasTo && children.length > 0) {
        throw new Error(
            "SidebarNode component: Passing both 'to' and 'active' props or 'children' prop is not supported"
        );
    }

    if (!hasTo && children.length === 0) {
        throw new Error("SidebarNode component: Passing 'to' and 'active' props or 'children' prop is required");
    }

    let isAnyChildActive = false;
    children.forEach((childNode) => {
        if (isAnyChildActive) {
            return;
        }

        if (isLocationActive(location.pathname, childNode.active)) {
            isAnyChildActive = true;
        }
    });

    const [open, setOpen] = React.useState(isAnyChildActive);

    if (!isAccessGranted(roleCheckerProps)) {
        return null;
    }

    return (
        <List {...{ disablePadding: true }}>
            {children.length === 0 ? (
                <ListItem {...{ disablePadding: true }}>
                    <ListItemButton
                        {...{
                            component: Link,
                            to,
                            selected: isLocationActive(location.pathname, active as string),
                            onClick: () => setMobileOpen(false),
                        }}
                    >
                        <ListItemIcon>{icon}</ListItemIcon>
                        <ListItemText>{t(label)}</ListItemText>
                    </ListItemButton>
                </ListItem>
            ) : (
                <>
                    <ListItem {...{ disablePadding: true, sx: { backgroundColor: open ? "#f9f9f9" : undefined } }}>
                        <ListItemButton {...{ onClick: () => setOpen(!open), selected: isAnyChildActive }}>
                            <ListItemIcon>{icon}</ListItemIcon>
                            <ListItemText>{t(label)}</ListItemText>
                            {open ? <ExpandLessOutlined /> : <ExpandMoreOutlined />}
                        </ListItemButton>
                    </ListItem>
                    <Collapse in={open} timeout="auto" unmountOnExit>
                        <List {...{ disablePadding: true, sx: { backgroundColor: open ? "#f9f9f9" : undefined } }}>
                            {children.map((childNode, key) => (
                                <SidebarChildNode key={key} {...childNode} />
                            ))}
                        </List>
                    </Collapse>
                </>
            )}
        </List>
    );
};

export default SidebarNode;
export { SidebarNodeProps };
