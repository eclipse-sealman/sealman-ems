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
import { Box, Divider, ListItemIcon, ListItemText, Menu, MenuItem } from "@mui/material";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Button } from "@arteneo/forge";
import { usePopupState, bindTrigger, bindMenu } from "material-ui-popup-state/hooks";
import {
    AccessTimeOutlined,
    BuildOutlined,
    DeveloperBoardOutlined,
    KeyboardArrowDownOutlined,
    LockOutlined,
    OpenInNewOutlined,
    PieChart,
    PowerSettingsNewOutlined,
    SettingsInputHdmi,
    SettingsOutlined,
    ShieldOutlined,
} from "@mui/icons-material";
import { useUtils } from "@mui/x-date-pickers/internals/hooks/useUtils";
import { isValid } from "date-fns";
import { useUser } from "~app/contexts/User";
import { useConfiguration } from "~app/contexts/Configuration";

const HeaderProfile = () => {
    const { t } = useTranslation();
    const popupState = usePopupState({ variant: "popover", popupId: "header-profile" });
    const { representation, lastLoginAt, logout, isAccessGranted, isRadiusUser, isSsoUser } = useUser();
    const {
        disableAdminRestApiDocumentation,
        disableSmartemsRestApiDocumentation,
        disableVpnSecuritySuiteRestApiDocumentation,
    } = useConfiguration();
    const utils = useUtils();

    let formattedLastLoginAt: undefined | string = undefined;
    if (typeof lastLoginAt !== "undefined") {
        const lastLoginAtDate = utils.date(lastLoginAt);
        if (isValid(lastLoginAtDate)) {
            formattedLastLoginAt = utils.format(lastLoginAtDate, "fullTime24h");
        }
    }

    let documentationLink: undefined | string = undefined;
    if (isAccessGranted({ admin: true }) && !disableAdminRestApiDocumentation) {
        documentationLink = "/web/doc/admin";
    }
    if (isAccessGranted({ smartems: true }) && !disableSmartemsRestApiDocumentation) {
        documentationLink = "/web/doc/smartems";
    }
    if (isAccessGranted({ vpn: true }) && !disableVpnSecuritySuiteRestApiDocumentation) {
        documentationLink = "/web/doc/vpnsecuritysuite";
    }
    if (
        isAccessGranted({ vpn: true }) &&
        isAccessGranted({ smartems: true }) &&
        !disableSmartemsRestApiDocumentation &&
        !disableVpnSecuritySuiteRestApiDocumentation
    ) {
        documentationLink = "/web/doc/smartemsvpnsecuritysuite";
    }

    return (
        <Box {...{ sx: { display: "flex", flexDirection: "row", alignItems: "center" } }}>
            <Button
                {...{
                    color: "inherit",
                    endIcon: <KeyboardArrowDownOutlined />,
                    sx: {
                        padding: "3px 12px",
                    },
                    ...bindTrigger(popupState),
                }}
            >
                <Box
                    {...{
                        sx: (theme) => ({
                            [theme.breakpoints.down("sm")]: {
                                maxWidth: 130,
                                textOverflow: "ellipsis",
                                overflow: "hidden",
                                whiteSpace: "nowrap",
                            },
                        }),
                    }}
                >
                    {representation}
                </Box>
            </Button>
            <Menu
                {...{
                    ...bindMenu(popupState),
                    elevation: 4,
                    anchorOrigin: {
                        vertical: "bottom",
                        horizontal: "right",
                    },
                    transformOrigin: {
                        vertical: "top",
                        horizontal: "right",
                    },
                }}
            >
                {typeof formattedLastLoginAt !== "undefined" && (
                    <MenuItem
                        {...{
                            disabled: true,
                            sx: { backgroundColor: "#f3f3f3", "&.Mui-disabled": { opacity: 1 }, py: 1, mb: 1 },
                        }}
                    >
                        <ListItemIcon>
                            <AccessTimeOutlined {...{ fontSize: "small" }} />
                        </ListItemIcon>
                        <ListItemText>
                            {t("header.profile.lastLoginAt")}
                            <Box {...{ component: "span", sx: { pl: 0.5, color: "primary.main", fontWeight: "700" } }}>
                                {formattedLastLoginAt}
                            </Box>
                        </ListItemText>
                    </MenuItem>
                )}
                {!isRadiusUser() && !isSsoUser() && (
                    <MenuItem
                        {...{
                            to: "/authenticated/change/password",
                            component: Link,
                            onClick: () => {
                                popupState.close();
                            },
                        }}
                    >
                        <ListItemIcon>
                            <LockOutlined {...{ fontSize: "small" }} />
                        </ListItemIcon>
                        <ListItemText>{t("header.profile.changePassword")}</ListItemText>
                    </MenuItem>
                )}
                {isAccessGranted({ adminVpn: true, vpn: true }) && (
                    <MenuItem
                        {...{
                            to: "/profile/vpn/details",
                            component: Link,
                            onClick: () => {
                                popupState.close();
                            },
                        }}
                    >
                        <ListItemIcon>
                            <SettingsInputHdmi {...{ fontSize: "small" }} />
                        </ListItemIcon>
                        <ListItemText>{t("header.profile.openVpnConnection")}</ListItemText>
                    </MenuItem>
                )}
                <MenuItem
                    {...{
                        to: "/profile/certificates",
                        component: Link,
                        onClick: () => {
                            popupState.close();
                        },
                    }}
                >
                    <ListItemIcon>
                        <ShieldOutlined {...{ fontSize: "small" }} />
                    </ListItemIcon>
                    <ListItemText>{t("header.profile.userCertificates")}</ListItemText>
                </MenuItem>
                {isAccessGranted({ admin: true }) && (
                    <MenuItem
                        {...{
                            to: "/maintenance/dashboard",
                            component: Link,
                            onClick: () => {
                                popupState.close();
                            },
                        }}
                    >
                        <ListItemIcon>
                            <BuildOutlined {...{ fontSize: "small" }} />
                        </ListItemIcon>
                        <ListItemText>{t("header.profile.maintenance")}</ListItemText>
                    </MenuItem>
                )}
                {isAccessGranted({ admin: true }) && (
                    <MenuItem
                        {...{
                            to: "/configuration/dashboard",
                            component: Link,
                            onClick: () => {
                                popupState.close();
                            },
                        }}
                    >
                        <ListItemIcon>
                            <SettingsOutlined {...{ fontSize: "small" }} />
                        </ListItemIcon>
                        <ListItemText>{t("header.profile.configuration")}</ListItemText>
                    </MenuItem>
                )}
                {typeof documentationLink !== "undefined" && (
                    <MenuItem
                        {...{
                            href: documentationLink,
                            target: "_blank",
                            component: "a",
                            onClick: () => {
                                popupState.close();
                            },
                        }}
                    >
                        <ListItemIcon>
                            <OpenInNewOutlined {...{ fontSize: "small" }} />
                        </ListItemIcon>
                        <ListItemText>{t("header.profile.documentation")}</ListItemText>
                    </MenuItem>
                )}
                {isAccessGranted({ admin: true }) && (
                    <MenuItem
                        {...{
                            to: "/opensourcelicense/list",
                            component: Link,
                            onClick: () => {
                                popupState.close();
                            },
                        }}
                    >
                        <ListItemIcon>
                            <DeveloperBoardOutlined {...{ fontSize: "small" }} />
                        </ListItemIcon>
                        <ListItemText>{t("header.profile.openSourceLicense")}</ListItemText>
                    </MenuItem>
                )}
                {isAccessGranted({ admin: true }) && (
                    <MenuItem
                        {...{
                            to: "/status",
                            component: Link,
                            onClick: () => {
                                popupState.close();
                            },
                        }}
                    >
                        <ListItemIcon>
                            <PieChart {...{ fontSize: "small" }} />
                        </ListItemIcon>
                        <ListItemText>{t("header.profile.status")}</ListItemText>
                    </MenuItem>
                )}
                <Divider />
                <MenuItem
                    {...{
                        onClick: () => {
                            logout();
                            popupState.close();
                        },
                    }}
                >
                    <ListItemIcon>
                        <PowerSettingsNewOutlined {...{ fontSize: "small" }} />
                    </ListItemIcon>
                    <ListItemText>{t("header.profile.logout")}</ListItemText>
                </MenuItem>
            </Menu>
        </Box>
    );
};

export default HeaderProfile;
