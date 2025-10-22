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
import {
    getLastAlive,
    getRefreshTokenExpiration,
    getSessionTimeout,
    isRefreshTokenExpired,
} from "~app/utilities/authentication";
import HeaderSessionExpiresIn from "~app/components/Layout/HeaderSessionExpiresIn";
import HeaderSessionKeepAlive from "~app/components/Layout/HeaderSessionKeepAlive";
import { useUser } from "~app/contexts/User";

const HeaderSession = () => {
    const { logout } = useUser();
    // undefined value is used when it could not be determined or is already expired
    const [sessionExpireTimestamp, setSessionExpireTimestamp] = React.useState<undefined | number>(undefined);

    const processSessionExpireTimestamp = (): void => {
        if (isRefreshTokenExpired()) {
            setSessionExpireTimestamp(() => undefined);
            logout();
            return;
        }

        const sessionTimeout = getSessionTimeout();
        if (typeof sessionTimeout === "undefined") {
            setSessionExpireTimestamp(() => undefined);
            logout();
            return;
        }

        const lastAlive: undefined | number = getLastAlive();
        const sessionExpireTimestamp =
            typeof lastAlive === "number" ? lastAlive + sessionTimeout : getRefreshTokenExpiration();
        if (typeof sessionExpireTimestamp === "undefined") {
            setSessionExpireTimestamp(() => undefined);
            logout();
            return;
        }

        const nowTimestamp = Math.round(Date.now() / 1000);
        if (nowTimestamp >= sessionExpireTimestamp) {
            setSessionExpireTimestamp(() => undefined);
            logout();
            return;
        }

        setSessionExpireTimestamp(() => sessionExpireTimestamp);
    };

    React.useEffect(() => {
        processSessionExpireTimestamp();

        const intervalId = setInterval(() => {
            processSessionExpireTimestamp();
        }, 1000);

        return () => clearInterval(intervalId);
    }, []);

    if (typeof sessionExpireTimestamp === "undefined") {
        return null;
    }

    return (
        <>
            <HeaderSessionExpiresIn {...{ sessionExpireTimestamp }} />
            <HeaderSessionKeepAlive {...{ sessionExpireTimestamp }} />
        </>
    );
};

export default HeaderSession;
