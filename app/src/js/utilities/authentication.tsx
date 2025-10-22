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

import axios, { AxiosError, AxiosInstance, AxiosResponse, InternalAxiosRequestConfig } from "axios";
import jwtDecode from "jwt-decode";

const AUTHENTICATION_DATA_STORAGE_KEY = "authentication_data";
const LAST_ALIVE_STORAGE_KEY = "last_alive";
// Token expire fudge determines how many seconds before access token expiration we should refresh it
const TOKEN_EXPIRE_FUDGE = 5;

type TokenType = string;

interface AuthenticationDataInterface {
    accessToken: TokenType;
    refreshToken: TokenType;
    refreshTokenExpiration: number;
    sessionTimeout: number;
    accessTokenTtl: number;
    totpSecret?: string;
    totpUrl?: string;
}

type AuthenticationTokenRefreshRequest = (refreshToken: TokenType) => Promise<TokenType | AuthenticationDataInterface>;

type RequestsQueue = {
    resolve: (value?: unknown) => void;
    reject: (reason?: unknown) => void;
}[];

let queue: RequestsQueue = [];
let isRefreshing = false;

const resolveQueue = (token?: TokenType) => {
    queue.forEach((p) => {
        p.resolve(token);
    });

    queue = [];
};

const declineQueue = (error: Error) => {
    queue.forEach((p) => {
        p.reject(error);
    });

    queue = [];
};

const setLastAlive = (lastAlive: number): void => {
    localStorage.setItem(LAST_ALIVE_STORAGE_KEY, lastAlive.toString());
};

const getLastAlive = (): undefined | number => {
    const lastAliveRaw = localStorage.getItem(LAST_ALIVE_STORAGE_KEY);
    if (!lastAliveRaw) {
        return;
    }

    const lastAlive = parseInt(lastAliveRaw, 10);
    if (isNaN(lastAlive)) {
        return;
    }

    return lastAlive;
};

const updateLastAlive = (): void => {
    setLastAlive(Math.round(Date.now() / 1000));
};

const setAuthenticationData = (data: AuthenticationDataInterface): void => {
    localStorage.setItem(AUTHENTICATION_DATA_STORAGE_KEY, JSON.stringify(data));
};

const getAuthenticationData = (): undefined | AuthenticationDataInterface => {
    const storedData = localStorage.getItem(AUTHENTICATION_DATA_STORAGE_KEY);
    if (!storedData) {
        return;
    }

    try {
        return JSON.parse(storedData);
    } catch (error: unknown) {
        if (error instanceof SyntaxError) {
            error.message = "Could not parse authentication data " + storedData;
            throw error;
        }
    }
};

const clearAuthenticationData = (): void => {
    localStorage.removeItem(AUTHENTICATION_DATA_STORAGE_KEY);
};

const getAccessToken = (): undefined | TokenType => {
    const authenticationData = getAuthenticationData();
    return authenticationData?.accessToken;
};

const setAccessToken = (token: TokenType): void => {
    const authenticationData = getAuthenticationData();
    if (!authenticationData) {
        throw new Error("Unable to update access token since there are no data currently stored");
    }

    authenticationData.accessToken = token;
    setAuthenticationData(authenticationData);
};

const getRefreshToken = (): undefined | TokenType => {
    const authenticationData = getAuthenticationData();
    return authenticationData?.refreshToken;
};

const setRefreshToken = (token: TokenType): void => {
    const authenticationData = getAuthenticationData();
    if (!authenticationData) {
        throw new Error("Unable to update refresh token since there are no data currently stored");
    }

    authenticationData.refreshToken = token;
    setAuthenticationData(authenticationData);
};

const setRefreshTokenExpiration = (refreshTokenExpiration: number): void => {
    const authenticationData = getAuthenticationData();
    if (!authenticationData) {
        throw new Error("Unable to update refresh token expiration since there are no data currently stored");
    }

    authenticationData.refreshTokenExpiration = refreshTokenExpiration;
    setAuthenticationData(authenticationData);
};

const getRefreshTokenExpiration = (): undefined | number => {
    const authenticationData = getAuthenticationData();
    return authenticationData?.refreshTokenExpiration;
};

const getSessionTimeout = (): undefined | number => {
    const authenticationData = getAuthenticationData();
    return authenticationData?.sessionTimeout;
};

const getAccessTokenTtl = (): undefined | number => {
    const authenticationData = getAuthenticationData();
    return authenticationData?.accessTokenTtl;
};

const applyAuthenticationInterceptor = (
    axios: AxiosInstance,
    disableUpdateLastAlive = false,
    url: null | string = "/authentication/token/refresh"
): void => {
    if (!axios.interceptors) {
        throw new Error("Invalid axios instance" + axios);
    }

    axios.interceptors.request.use(authenticationInterceptor(disableUpdateLastAlive, url));
};

const authenticationInterceptor = (
    disableUpdateLastAlive = false,
    url: null | string = "/authentication/token/refresh"
) => {
    return async (requestConfig: InternalAxiosRequestConfig): Promise<InternalAxiosRequestConfig> => {
        // Refresh token is needed to do any authenticated requests
        if (!getRefreshToken()) return requestConfig;

        // Queue the request if another refresh request is currently happening
        if (isRefreshing) {
            return new Promise((resolve, reject) => {
                queue.push({ resolve, reject });
            })
                .then((token) => {
                    if (requestConfig.headers) {
                        requestConfig.headers["Authorization"] = "Bearer " + token;
                    }

                    return requestConfig;
                })
                .catch(Promise.reject.bind(Promise));
        }

        // Do refresh if needed
        let accessToken;
        try {
            accessToken = await refreshAccessTokenWhenNeeded(disableUpdateLastAlive, url);
            resolveQueue(accessToken);
        } catch (error: unknown) {
            if (error instanceof Error) {
                // Prepare AxiosError instance to be interpreted as 401 response by handleCatch
                const axiosError = new AxiosError(
                    "Unable to refresh access token for request due to token refresh error " + error.message,
                    undefined,
                    undefined,
                    undefined,
                    {
                        status: 401,
                    } as AxiosResponse
                );
                declineQueue(axiosError);

                throw new Error(
                    "Unable to refresh access token for request due to token refresh error " + error.message
                );
            }
        }

        // Add token to headers
        if (accessToken && requestConfig.headers) {
            requestConfig.headers["Authorization"] = "Bearer " + accessToken;
        }

        return requestConfig;
    };
};

const refreshAccessTokenWhenNeeded = async (
    disableUpdateLastAlive = false,
    url: null | string = "/authentication/token/refresh"
): Promise<TokenType | undefined> => {
    let accessToken = getAccessToken();
    if (url !== null && (!accessToken || isTokenExpired(accessToken))) {
        accessToken = await refreshAccessToken(url);
    }

    if (!disableUpdateLastAlive) {
        updateLastAlive();
    }

    return accessToken;
};

const refreshAccessToken = async (url = "/authentication/token/refresh"): Promise<TokenType> => {
    const refreshToken = getRefreshToken();
    if (!refreshToken) {
        throw new Error("No refresh token available");
    }

    try {
        isRefreshing = true;

        // Refresh and store access token using the supplied refresh function
        // Different instance needs to be used to avoid infinite loop
        const axiosInstance = axios.create();
        const response = await axiosInstance.post(url, { refreshToken });
        const authenticationData: AuthenticationDataInterface = {
            refreshToken: response.data.refreshToken,
            refreshTokenExpiration: response.data.refreshTokenExpiration,
            accessToken: response.data.token,
            sessionTimeout: response.data.sessionTimeout,
            accessTokenTtl: response.data.accessTokenTtl,
        };

        await setAuthenticationData(authenticationData);
        return authenticationData.accessToken;
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
    } catch (error: any) {
        // Failed to refresh token
        const status = error?.response?.status;
        if (status === 401 || status === 422) {
            clearAuthenticationData();
            throw new Error("Got " + status + " while refreshing token. Authentication data cleared");
        } else {
            throw new Error("Failed to refresh token " + error.message);
        }
    } finally {
        isRefreshing = false;
    }
};

const isTokenExpired = (token: TokenType): boolean => {
    if (!token) {
        return true;
    }

    const expiresIn = getTokenExpiresIn(token);
    return expiresIn <= TOKEN_EXPIRE_FUDGE;
};

const getTokenExpTimestamp = (token: TokenType): number | undefined => {
    const decoded = jwtDecode<{ [key: string]: number }>(token);
    return decoded?.exp;
};

const getTokenExpiresIn = (token: TokenType): number => {
    const expiration = getTokenExpTimestamp(token);

    if (!expiration) {
        return -1;
    }

    return expiration - Date.now() / 1000;
};

const isRefreshTokenExpired = (): boolean => {
    const refreshTokenExpiration = getRefreshTokenExpiration();
    if (typeof refreshTokenExpiration === "undefined") {
        return true;
    }

    return refreshTokenExpiration - Date.now() / 1000 <= 0;
};

const applyAxiosBaseUrl = (axios: AxiosInstance, baseUrl?: string): void => {
    axios.defaults.baseURL = baseUrl;
};

export {
    AUTHENTICATION_DATA_STORAGE_KEY,
    LAST_ALIVE_STORAGE_KEY,
    TOKEN_EXPIRE_FUDGE,
    TokenType,
    AuthenticationDataInterface,
    AuthenticationTokenRefreshRequest,
    setLastAlive,
    getLastAlive,
    updateLastAlive,
    setAuthenticationData,
    getAuthenticationData,
    clearAuthenticationData,
    getAccessToken,
    setAccessToken,
    getRefreshToken,
    setRefreshToken,
    getRefreshTokenExpiration,
    setRefreshTokenExpiration,
    applyAuthenticationInterceptor,
    getSessionTimeout,
    getAccessTokenTtl,
    isRefreshTokenExpired,
    getTokenExpTimestamp,
    refreshAccessToken,
    applyAxiosBaseUrl,
};
