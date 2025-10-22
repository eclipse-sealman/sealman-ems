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
import {
    getAccessTokenTtl,
    getRefreshToken,
    getRefreshTokenExpiration,
    setRefreshTokenExpiration,
    TOKEN_EXPIRE_FUDGE,
} from "~app/utilities/authentication";

interface HeaderSessionKeepAliveProps {
    sessionExpireTimestamp: number;
}

let extendingRefreshToken = false;

const HeaderSessionKeepAlive = ({ sessionExpireTimestamp }: HeaderSessionKeepAliveProps) => {
    const [refreshCounter, setRefreshCounter] = React.useState<number>(0);

    const extendRefreshToken = () => {
        extendingRefreshToken = true;

        // Different instance needs to be used to skip existing interceptors
        const axiosInstance = axios.create();
        axiosInstance
            .get("/authentication/token/extend/" + getRefreshToken())
            .then(() => {
                const accessTokenTtl = getAccessTokenTtl();
                if (typeof accessTokenTtl === "undefined") {
                    // Do nothing when accessTokenTtl is unknown
                    return;
                }

                setRefreshTokenExpiration(Math.round(Date.now() / 1000) + accessTokenTtl);
                extendingRefreshToken = false;
            })
            .catch(() => {
                // Do nothing
            });
    };

    const keepAlive = () => {
        const nowTimestamp = Math.round(Date.now() / 1000);

        const accessTokenTtl = getAccessTokenTtl();
        if (typeof accessTokenTtl === "undefined") {
            // Do nothing when accessTokenTtl is unknown
            return;
        }

        const refreshTokenExpiration = getRefreshTokenExpiration();
        if (typeof refreshTokenExpiration === "undefined") {
            // Do nothing when refreshTokenExpiration is unknown
            return;
        }

        if (extendingRefreshToken) {
            return;
        }

        if (refreshTokenExpiration - sessionExpireTimestamp >= 0) {
            // Do nothing when refresh token expires after session expires
            return;
        }

        // Difference between session expire and refresh token expire is higher than access token ttl
        // Refresh token needs to be extended just before expiration
        if (sessionExpireTimestamp - refreshTokenExpiration > accessTokenTtl) {
            // Check whether it is just before expiration
            if (refreshTokenExpiration - nowTimestamp < TOKEN_EXPIRE_FUDGE) {
                extendRefreshToken();
            }

            return;
        }

        // Session expires in less than access token ttl
        // Refresh token needs to be extended
        if (sessionExpireTimestamp - nowTimestamp <= accessTokenTtl) {
            extendRefreshToken();
        }
    };

    React.useEffect(() => {
        // This interval makes sure that refresh token expiration time will be extended until session expiration
        keepAlive();

        const timeoutId = setTimeout(() => {
            // Fire this useEffect every second
            setRefreshCounter((refreshCounter) => ++refreshCounter);
        }, 1000);

        return () => clearTimeout(timeoutId);
    }, [refreshCounter]);

    // Renders nothing by design
    return null;
};

export default HeaderSessionKeepAlive;
