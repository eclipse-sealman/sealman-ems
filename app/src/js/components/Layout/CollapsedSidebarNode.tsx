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
import { List, ListItem, ListItemButton, ListItemIcon, ListItemText, MenuItem } from "@mui/material";
import { Link, useLocation } from "react-router-dom";
import HoverMenu from "material-ui-popup-state/HoverMenu";
import { usePopupState, bindHover, bindMenu } from "material-ui-popup-state/hooks";
import { isLocationActive } from "~app/utilities/location";
import { SidebarNodeProps } from "~app/components/Layout/SidebarNode";
import CollapsedSidebarChildNode from "~app/components/Layout/CollapsedSidebarChildNode";
import { useUser } from "~app/contexts/User";

const CollapsedSidebarNode = ({ label, to, active, icon, children = [], ...roleCheckerProps }: SidebarNodeProps) => {
    const { isAccessGranted } = useUser();

    if (!isAccessGranted(roleCheckerProps)) {
        return null;
    }

    const { t } = useTranslation();
    const location = useLocation();
    const popupState = usePopupState({ variant: "popover", popupId: label });

    const hasTo = typeof to !== "undefined" && typeof active !== "undefined";

    if (hasTo && children.length > 0) {
        throw new Error(
            "CollapsedSidebarNode component: Passing both 'to' and 'active' props or 'children' prop is not supported"
        );
    }

    if (!hasTo && children.length === 0) {
        throw new Error(
            "CollapsedSidebarNode component: Passing 'to' and 'active' props or 'children' prop is required"
        );
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

    return (
        <>
            <List {...{ disablePadding: true }}>
                <ListItem {...{ disablePadding: true }}>
                    {children.length === 0 ? (
                        <ListItemButton
                            {...{
                                component: Link,
                                to,
                                selected: isLocationActive(location.pathname, active as string),
                                sx: {
                                    backgroundColor: popupState.isOpen ? "#f5f5f5" : undefined,
                                    "&.Mui-selected, &.Mui-selected:hover": {
                                        backgroundColor: popupState.isOpen ? "#f5f5f5" : undefined,
                                    },
                                },
                                ...bindHover(popupState),
                            }}
                        >
                            <ListItemIcon {...{ sx: { minWidth: 0 } }}>{icon}</ListItemIcon>
                        </ListItemButton>
                    ) : (
                        <ListItemButton
                            {...{
                                selected: isAnyChildActive,
                                disableRipple: true,
                                sx: {
                                    cursor: "default",
                                    backgroundColor: popupState.isOpen ? "#f5f5f5" : undefined,
                                    "&.Mui-selected, &.Mui-selected:hover": {
                                        backgroundColor: popupState.isOpen ? "#f5f5f5" : undefined,
                                    },
                                },
                                ...bindHover(popupState),
                            }}
                        >
                            <ListItemIcon {...{ sx: { minWidth: 0 } }}>{icon}</ListItemIcon>
                        </ListItemButton>
                    )}
                </ListItem>
            </List>
            <HoverMenu
                {...{
                    ...bindMenu(popupState),
                    elevation: 4,
                    anchorOrigin: {
                        vertical: "top",
                        horizontal: "right",
                    },
                    transformOrigin: {
                        vertical: "top",
                        horizontal: "left",
                    },
                    MenuListProps: {
                        sx: {
                            py: 0,
                        },
                    },
                    PaperProps: {
                        elevation: 1,
                        sx: {
                            borderTopLeftRadius: 0,
                            borderBottomLeftRadius: 0,
                            backgroundColor: "#ffffff",
                            border: "1px solid #e9e9e9",
                            borderLeft: 0,
                            marginTop: "-1px",
                            boxShadow: "none",
                            zIndex: (theme) => theme.zIndex.drawer - 10,
                        },
                    },
                }}
            >
                {children.length === 0 ? (
                    <MenuItem
                        {...{
                            component: Link,
                            to,
                            selected: isLocationActive(location.pathname, active as string),
                            onClick: () => {
                                popupState.close();
                            },
                            sx: {
                                padding: 0,
                                marginLeft: "-8px",
                                marginRight: "-8px",
                                paddingLeft: "32px",
                                paddingRight: "32px",
                                borderRadius: 0,
                                backgroundColor: "#ffffff",
                                transition: "all 0.15s ease-in",
                                "&.Mui-selected, &:hover, &.Mui-selected:hover": {
                                    color: "primary.main",
                                    backgroundColor: "#ffffff",
                                },
                            },
                        }}
                    >
                        <ListItemText {...{ disableTypography: true, sx: { px: 2, py: 1, fontWeight: 600 } }}>
                            {t(label)}
                        </ListItemText>
                    </MenuItem>
                ) : (
                    <MenuItem
                        {...{
                            disabled: true,
                            sx: {
                                backgroundColor: "#f5f5f5",
                                "&.Mui-disabled": { opacity: 1 },
                                marginTop: "-8px",
                                marginLeft: "-8px",
                                marginRight: "-8px",
                                paddingLeft: "24px",
                                paddingTop: "17px",
                                paddingBottom: "11px",
                                borderRadius: 0,
                            },
                            onClick: () => {
                                popupState.close();
                            },
                        }}
                    >
                        <ListItemText>{t(label)}</ListItemText>
                    </MenuItem>
                )}
                {children.map((childNode, key) => (
                    <CollapsedSidebarChildNode key={key} {...childNode} />
                ))}
            </HoverMenu>
        </>
    );
};

export default CollapsedSidebarNode;
