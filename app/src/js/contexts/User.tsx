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
import { useNavigate } from "react-router-dom";
import {
    AuthenticationDataInterface,
    clearAuthenticationData,
    getRefreshToken,
    setAuthenticationData,
    updateLastAlive,
} from "~app/utilities/authentication";

const USER_STORAGE_KEY = "user";

interface UserInterface {
    username?: string;
    representation?: string;
    lastLoginAt?: string;
    roles: string[];
}

interface RoleCheckerProps {
    admin?: boolean;
    adminVpn?: boolean;
    adminScep?: boolean;
    smartems?: boolean;
    vpn?: boolean;
    vpnEndpointDevices?: boolean;
    authenticated?: boolean;
}

type LoginInterface = UserInterface & AuthenticationDataInterface;

interface UserContextProps {
    username?: string;
    representation?: string;
    lastLoginAt?: string;
    roles: string[];
    totpSecret?: string;
    totpUrl?: string;
    login: (data: LoginInterface) => void;
    logout: (callback?: () => void) => void;
    navigateHomepage: () => void;
    getHomepage: () => string;
    isAdmin: () => boolean;
    isAdminVpn: () => boolean;
    isAdminScep: () => boolean;
    isSmartems: () => boolean;
    isVpn: () => boolean;
    isVpnEndpointDevices: () => boolean;
    isAuthenticated: () => boolean;
    isRadiusUser: () => boolean;
    isSsoUser: () => boolean;
    isChangePasswordRequired: () => boolean;
    isTotpRequired: () => boolean;
    isAccessGranted: (props: RoleCheckerProps) => boolean;
}

interface UserProviderProps {
    children: React.ReactNode;
}

const contextInitial = {
    roles: [],
    login: () => {
        // tslint:disable:no-empty
    },
    logout: () => {
        // tslint:disable:no-empty
    },
    navigateHomepage: () => {
        // tslint:disable:no-empty
    },
    getHomepage: () => "",
    isAdmin: () => false,
    isAdminVpn: () => false,
    isAdminScep: () => false,
    isSmartems: () => false,
    isVpn: () => false,
    isVpnEndpointDevices: () => false,
    isAuthenticated: () => false,
    isRadiusUser: () => false,
    isSsoUser: () => false,
    isChangePasswordRequired: () => false,
    isTotpRequired: () => false,
    isAccessGranted: () => false,
};

const UserContext = React.createContext<UserContextProps>(contextInitial);

const UserProvider = ({ children }: UserProviderProps) => {
    const navigate = useNavigate();

    const getInitialUser = (): undefined | UserInterface => {
        const storedUser = localStorage.getItem(USER_STORAGE_KEY);
        if (!storedUser) {
            return;
        }

        try {
            return JSON.parse(storedUser);
        } catch (error) {
            return;
        }
    };

    const initialUser = getInitialUser();

    const [username, setUsername] = React.useState<undefined | string>(initialUser?.username);
    const [representation, setRepresentation] = React.useState<undefined | string>(initialUser?.representation);
    const [lastLoginAt, setLastLoginAt] = React.useState<undefined | string>(initialUser?.lastLoginAt);
    const [roles, setRoles] = React.useState<string[]>(initialUser?.roles ?? []);
    const [totpSecret, setTotpSecret] = React.useState<undefined | string>(undefined);
    const [totpUrl, setTotpUrl] = React.useState<undefined | string>(undefined);
    const [redirectAfterRolesUpdate, setRedirectAfterRolesUpdate] = React.useState<string>("");

    React.useEffect(() => {
        // We can redirect only after roles has been updated to allow valid security tests deeper in the application
        if (redirectAfterRolesUpdate) {
            navigate(redirectAfterRolesUpdate);
            setRedirectAfterRolesUpdate("");
        }
    }, [redirectAfterRolesUpdate]);

    const setUser = (user: UserInterface) => {
        localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user));
    };

    const clearUser = () => {
        localStorage.removeItem(USER_STORAGE_KEY);
    };

    const login = ({
        username,
        representation,
        lastLoginAt,
        roles,
        totpSecret,
        totpUrl,
        accessToken,
        refreshToken,
        refreshTokenExpiration,
        sessionTimeout,
        accessTokenTtl,
    }: LoginInterface) => {
        // Data is saved in local storage to be available after reloading
        setUser({ username, representation, lastLoginAt, roles });

        setUsername(username);
        setRepresentation(representation);
        setLastLoginAt(lastLoginAt);
        setRoles(roles);

        const hasTotpSecret = totpSecret && totpUrl ? true : false;
        if (hasTotpSecret) {
            // TOTP secret and url are not stored in local storage by design
            setTotpSecret(totpSecret);
            setTotpUrl(totpUrl);
        }

        setAuthenticationData({
            accessToken,
            refreshToken,
            refreshTokenExpiration,
            sessionTimeout,
            accessTokenTtl,
        });
        updateLastAlive();

        if (isChangePasswordRequired(roles)) {
            setRedirectAfterRolesUpdate("/authentication/change/password/required");
        } else if (hasTotpSecret) {
            setRedirectAfterRolesUpdate("/authentication/totp/secret");
        } else if (isTotpRequired(roles)) {
            setRedirectAfterRolesUpdate("/authentication/totp/required");
        } else {
            setRedirectAfterRolesUpdate(getHomepage(roles));
        }
    };

    const logout = (callback?: () => void) => {
        const doLogout = () => {
            clearUser();
            setUsername(undefined);
            setRepresentation(undefined);
            setLastLoginAt(undefined);
            setRoles([]);
            setTotpSecret(undefined);
            setTotpUrl(undefined);
            clearAuthenticationData();
            navigate("/authentication/login");
        };

        // Our job is just to let know the backend to logout the user (it should handle refresh token invalidation).
        // We logout the user from our application on success and on error.
        axios.post("/authenticated/logout", { refreshToken: getRefreshToken() }).finally(() => {
            doLogout();
            if (typeof callback !== "undefined") {
                callback();
            }
        });
    };

    const navigateHomepage = (roles: string[]) => {
        navigate(getHomepage(roles));
    };

    const getHomepage = (roles: string[]) => {
        if (isAdmin(roles)) {
            return "/device/list";
        }

        if (isSmartems(roles)) {
            return "/device/list";
        }

        if (isVpn(roles)) {
            return "/device/list";
        }

        return "/authenticated/noaccess";
    };

    const isTotpRequired = (roles: string[]) => {
        return roles.includes("ROLE_TOTPREQUIRED");
    };

    const isChangePasswordRequired = (roles: string[]) => {
        return roles.includes("ROLE_CHANGEPASSWORDREQUIRED");
    };

    const isRadiusUser = (roles: string[]) => {
        return roles.includes("ROLE_RADIUSUSER");
    };

    const isSsoUser = (roles: string[]) => {
        return roles.includes("ROLE_SSOUSER");
    };

    const isAdmin = (roles: string[]) => {
        return roles.includes("ROLE_ADMIN");
    };

    const isAdminVpn = (roles: string[]) => {
        return roles.includes("ROLE_ADMIN_VPN");
    };

    const isAdminScep = (roles: string[]) => {
        return roles.includes("ROLE_ADMIN_SCEP");
    };

    const isSmartems = (roles: string[]) => {
        return roles.includes("ROLE_SMARTEMS");
    };

    const isVpn = (roles: string[]) => {
        return roles.includes("ROLE_VPN");
    };

    const isVpnEndpointDevices = (roles: string[]) => {
        return roles.includes("ROLE_VPN_ENDPOINTDEVICES");
    };

    const isAuthenticated = (roles: string[]) => {
        return roles.includes("ROLE_USER");
    };

    const isAccessGranted = (
        {
            admin = false,
            adminVpn = false,
            adminScep = false,
            smartems = false,
            vpn = false,
            vpnEndpointDevices = false,
            authenticated = false,
        }: RoleCheckerProps,
        roles: string[]
    ) => {
        if (
            (authenticated && isAuthenticated(roles)) ||
            (admin && isAdmin(roles)) ||
            (adminVpn && isAdminVpn(roles)) ||
            (adminScep && isAdminScep(roles)) ||
            (smartems && isSmartems(roles)) ||
            (vpnEndpointDevices && isVpnEndpointDevices(roles)) ||
            (vpn && isVpn(roles))
        ) {
            return true;
        }
        return false;
    };

    return (
        <UserContext.Provider
            value={{
                username,
                representation,
                lastLoginAt,
                roles,
                totpSecret,
                totpUrl,
                login,
                logout,
                navigateHomepage: () => navigateHomepage(roles),
                getHomepage: () => getHomepage(roles),
                isAdmin: () => isAdmin(roles),
                isAdminVpn: () => isAdminVpn(roles),
                isAdminScep: () => isAdminScep(roles),
                isSmartems: () => isSmartems(roles),
                isVpn: () => isVpn(roles),
                isVpnEndpointDevices: () => isVpnEndpointDevices(roles),
                isAuthenticated: () => isAuthenticated(roles),
                isChangePasswordRequired: () => isChangePasswordRequired(roles),
                isRadiusUser: () => isRadiusUser(roles),
                isSsoUser: () => isSsoUser(roles),
                isTotpRequired: () => isTotpRequired(roles),
                isAccessGranted: (props: RoleCheckerProps) => isAccessGranted(props, roles),
            }}
        >
            {children}
        </UserContext.Provider>
    );
};

const useUser = (): UserContextProps => React.useContext(UserContext);

export {
    UserContext,
    UserContextProps,
    UserProvider,
    UserProviderProps,
    useUser,
    UserInterface,
    LoginInterface,
    RoleCheckerProps,
};
