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
import { MenuItem, ListItemButton, ListItemText } from "@mui/material";
import { Link, useLocation } from "react-router-dom";
import { isLocationActive } from "~app/utilities/location";
import { SidebarChildNodeProps } from "~app/components/Layout/SidebarChildNode";

const CollapsedSidebarChildNode = ({ label, to, active }: SidebarChildNodeProps) => {
    const { t } = useTranslation();
    const location = useLocation();

    return (
        <MenuItem {...{ sx: { p: 0, my: 1, mx: 1, backgroundColor: "#ffffff" } }}>
            <ListItemButton
                {...{
                    component: Link,
                    to,
                    selected: isLocationActive(location.pathname, active),
                    sx: {
                        backgroundColor: "#ffffff",
                        border: 0,
                        borderRadius: "12px",
                        paddingLeft: "24px",
                        paddingRight: "24px",
                        transition: "all 0.15s ease-in",
                        "&:hover": {
                            color: "primary.main",
                            backgroundColor: "#f5f5f5",
                        },
                        "&.Mui-selected": {
                            color: "primary.main",
                            backgroundColor: "#ffffff",
                        },
                        "&.Mui-selected:hover": {
                            backgroundColor: "#f5f5f5",
                        },
                        "& > .MuiListItemText-root > .MuiListItemText-primary": {
                            fontSize: 16,
                        },
                    },
                }}
            >
                <ListItemText>{t(label)}</ListItemText>
            </ListItemButton>
        </MenuItem>
    );
};

export default CollapsedSidebarChildNode;
