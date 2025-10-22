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
import { ListItem, ListItemButton, ListItemText } from "@mui/material";
import { Link, useLocation } from "react-router-dom";
import { isLocationActive } from "~app/utilities/location";
import { useSidebar } from "~app/contexts/Sidebar";
import { RoleCheckerProps, useUser } from "~app/contexts/User";

interface SidebarChildNodeProps extends RoleCheckerProps {
    label: string;
    to: string;
    active: string;
}

const SidebarChildNode = ({ label, to, active, ...roleCheckerProps }: SidebarChildNodeProps) => {
    const { isAccessGranted } = useUser();

    if (!isAccessGranted(roleCheckerProps)) {
        return null;
    }

    const { t } = useTranslation();
    const location = useLocation();
    const { setMobileOpen } = useSidebar();

    return (
        <ListItem>
            <ListItemButton
                {...{
                    component: Link,
                    to,
                    selected: isLocationActive(location.pathname, active),
                    onClick: () => setMobileOpen(false),
                }}
            >
                <ListItemText>{t(label)}</ListItemText>
            </ListItemButton>
        </ListItem>
    );
};

export default SidebarChildNode;
export { SidebarChildNodeProps };
