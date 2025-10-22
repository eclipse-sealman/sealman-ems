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
import axios, { AxiosError, AxiosResponse } from "axios";
import {
    applyAuthenticationInterceptor,
    getAccessToken,
    getRefreshTokenExpiration,
    getTokenExpTimestamp,
    TOKEN_EXPIRE_FUDGE,
} from "~app/utilities/authentication";
import { UserInterface } from "~app/entities/User/definitions";
import HeaderVpnConnectionDisplay from "~app/components/Layout/HeaderVpnConnectionDisplay";
import { Box } from "@mui/system";
import { useUtils } from "@mui/x-date-pickers/internals";
import { format } from "date-fns";
import { ResultInterface, ErrorInterface } from "@arteneo/forge";

interface ConnectionInterface {
    id?: number;
    connectionStartAt?: string;
    connectionEndAt?: string;
    device?: ResultInterface;
    endpointDevice?: ResultInterface;
    user?: ResultInterface;
}

interface ConnectionStatusInterface {
    connections?: ConnectionInterface[];
    user?: UserInterface;
}

const HeaderVpnConnection = () => {
    const { t } = useTranslation();
    const utils = useUtils();

    const [refreshCounter, setRefreshCounter] = React.useState<undefined | number>(1);
    const [status, setStatus] = React.useState<undefined | ConnectionStatusInterface>(undefined);
    const [errors, setErrors] = React.useState<undefined | ErrorInterface[]>(undefined);

    React.useEffect(() => {
        // Delay refreshing connection status to avoid paralell requests from other parts of UI
        // 1000 ms is arbitrary value
        const delayTimeoutId = setTimeout(() => {
            refreshConnectionStatus();
        }, 1000);

        const timeoutId = setTimeout(() => {
            setRefreshCounter((refreshCounter) =>
                typeof refreshCounter === "undefined" ? undefined : refreshCounter + 1
            );
        }, 5000);

        return () => {
            clearTimeout(delayTimeoutId);
            clearTimeout(timeoutId);
        };
    }, [refreshCounter]);

    const refreshConnectionStatus = () => {
        if (typeof refreshCounter === "undefined") {
            // When refreshCounter is undefined this means we should stop refreshing
            return;
        }

        const accessToken = getAccessToken();
        if (typeof accessToken === "undefined") {
            // Do nothing when accessToken is unknown
            return;
        }

        const refreshTokenExpiration = getRefreshTokenExpiration();
        if (typeof refreshTokenExpiration === "undefined") {
            // Do nothing when refreshTokenExpiration is unknown
            return;
        }

        const accessTokenExpiration = getTokenExpTimestamp(accessToken);
        if (typeof accessTokenExpiration === "undefined") {
            // Do nothing when accessTokenExpiration is unknown
            return;
        }

        const nowTimestamp = Math.round(Date.now() / 1000);
        if (refreshTokenExpiration - nowTimestamp <= TOKEN_EXPIRE_FUDGE) {
            // Stop refreshing when refresh token expires in less than TOKEN_EXPIRE_FUDGE
            return;
        }

        // Avoid refreshing when difference between refresh token expire and access token expire is less than TOKEN_EXPIRE_FUDGE
        const shouldRefreshToken = refreshTokenExpiration - accessTokenExpiration >= TOKEN_EXPIRE_FUDGE;

        // Different instance needs to be used to disable default last alive update
        const axiosInstance = axios.create();
        applyAuthenticationInterceptor(
            axiosInstance,
            true,
            shouldRefreshToken ? "/authentication/token/keep-ttl-refresh" : null
        );

        axiosInstance
            .get("/vpn/connection/status")
            .then((response: AxiosResponse) => {
                setStatus(response.data);
            })
            .catch((error: AxiosError) => {
                if (error?.response?.status == 409) {
                    // eslint-disable-next-line
                    let data = (error?.response?.data || {}) as unknown as any;
                    if (data?.errors) {
                        setErrors(data.errors as ErrorInterface[]);
                    }
                }
                setRefreshCounter(undefined);
                // Change refreshCounter to undefined and stop refreshing
            });
    };

    //todo Arek maybe this should me moved to utils?
    const getFormatedDateTime = (value?: string) => {
        if (!value) {
            return null;
        }
        const dateValue = utils.date(value);
        if (dateValue == "Invalid Date") {
            console.warn("DateTimeSecondsColumn component: Could not parse date");
            return null;
        }

        return format(dateValue as Date, "dd-MM-yyyy HH:mm:ss");
    };

    const getTargetId = (connection?: ConnectionInterface) => {
        if (!connection) {
            return undefined;
        }
        if (connection.device) {
            return connection.device.id;
        }
        if (connection.endpointDevice) {
            return connection.endpointDevice.id;
        }
        return undefined;
    };

    const getTargetEntity = (connection?: ConnectionInterface) => {
        if (!connection) {
            return undefined;
        }
        if (connection.device) {
            return "device";
        }
        if (connection.endpointDevice) {
            return "deviceendpointdevice";
        }
        return undefined;
    };

    const getTargetRepresentation = (connection?: ConnectionInterface) => {
        if (!connection) {
            return undefined;
        }
        if (connection.device) {
            return connection.device.representation;
        }
        if (connection.endpointDevice) {
            return connection.endpointDevice.representation;
        }
        return undefined;
    };

    if (errors) {
        const errorMessage = errors.reduce((accumulator: string, error: ErrorInterface) => {
            if (accumulator.length > 0) {
                accumulator += " ";
            }
            accumulator += t(error.message, error.parameters ?? {});

            return accumulator;
        }, "");

        //todo Arek maybe message should be shown in tooltip if too long?

        return (
            <HeaderVpnConnectionDisplay
                title={errorMessage}
                to={"/profile/vpn/details"}
                buttonLabel={"header.connection.howToConnect"}
            />
        );
    }

    if (!status) {
        return null;
    }

    if (status?.connections && status?.connections.length > 0) {
        if (status?.connections && status?.connections.length > 1) {
            return (
                <HeaderVpnConnectionDisplay
                    title={"header.connection.multipleVpnConnections"}
                    to={"/vpnconnectionowned/list"}
                    buttonLabel={"header.connection.ownedVpnConnection"}
                />
            );
        } else {
            return (
                <HeaderVpnConnectionDisplay
                    title={"header.connection.connectedTo"}
                    titleVariables={{ target: getTargetRepresentation(status?.connections[0]) }}
                    to={
                        "/" +
                        getTargetEntity(status?.connections[0]) +
                        "/details/" +
                        getTargetId(status?.connections[0])
                    }
                    buttonLabel={"action.details"}
                >
                    <Box {...{ sx: { color: "text.secondary", fontSize: "0.85rem" } }}>
                        ({t("header.connection.since")}
                        <strong> {getFormatedDateTime(status?.connections[0]?.connectionStartAt)}</strong>
                        {status?.connections[0]?.connectionEndAt && (
                            <>
                                &nbsp;{t("header.connection.validTo")}
                                <strong> {getFormatedDateTime(status?.connections[0]?.connectionEndAt)}</strong>
                            </>
                        )}
                        )
                    </Box>
                </HeaderVpnConnectionDisplay>
            );
        }
    } else {
        if (status?.user && status?.user?.vpnConnected) {
            return (
                <HeaderVpnConnectionDisplay
                    title={"header.connection.deviceConnectionMissing"}
                    to={"/device/list"}
                    buttonLabel={"header.connection.clickToConnect"}
                >
                    <Box {...{ sx: { color: "text.secondary", fontSize: "0.85rem" } }}>
                        ({t("header.connection.youIpAddressIs")} <strong>{status?.user?.vpnIp}</strong>)
                    </Box>
                </HeaderVpnConnectionDisplay>
            );
        } else {
            return (
                <HeaderVpnConnectionDisplay
                    title={"header.connection.notConnected"}
                    to={"/profile/vpn/details"}
                    buttonLabel={"header.connection.howToConnect"}
                />
            );
        }
    }
};

export default HeaderVpnConnection;
