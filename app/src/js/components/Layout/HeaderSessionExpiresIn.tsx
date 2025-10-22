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
import { Box, Chip } from "@mui/material";
import { useTranslation } from "react-i18next";
import { updateLastAlive } from "~app/utilities/authentication";

interface HeaderSessionExpiresInProps {
    sessionExpireTimestamp: number;
}

const HeaderSessionExpiresIn = ({ sessionExpireTimestamp }: HeaderSessionExpiresInProps) => {
    const { t } = useTranslation();

    const getSessionExpiresIn = () => {
        return sessionExpireTimestamp - Math.round(Date.now() / 1000);
    };

    const [sessionExpiresIn, setSessionExpiresIn] = React.useState<number>(getSessionExpiresIn());

    React.useEffect(() => {
        setSessionExpiresIn(() => getSessionExpiresIn());

        const intervalId = setInterval(() => {
            setSessionExpiresIn(() => getSessionExpiresIn());
        }, 1000);

        return () => clearInterval(intervalId);
    }, [sessionExpireTimestamp]);

    if (sessionExpiresIn <= 0) {
        return null;
    }

    const minutesLeft = Math.floor(sessionExpiresIn / 60);
    const secondsLeft = sessionExpiresIn % 60;

    const timeLeft = minutesLeft.toString().padStart(2, "0") + ":" + secondsLeft.toString().padStart(2, "0");

    return (
        <Box
            {...{
                sx: {
                    display: "flex",
                    flexDirection: { xs: "column", sm: "row" },
                    alignItems: "center",
                    color: "text.secondary",
                    textAlign: { xs: "center", sm: "left" },
                    fontSize: { xs: "0.7rem", sm: "0.95rem" },
                },
            }}
        >
            <Box {...{ sx: { display: { xs: "none", sm: "inline" } } }}>{t("header.session.expireAt")}</Box>
            <Box {...{ sx: { display: { xs: "inline", sm: "none" } } }}>{t("header.session.expireAtMobile")}</Box>
            <Chip
                {...{
                    label: timeLeft,
                    variant: "outlined",
                    sx: {
                        ml: 1,
                        height: { xs: 24, sm: "inherit" },
                        borderRadius: "12px",
                        borderWidth: minutesLeft >= 5 ? undefined : 2,
                        borderColor: minutesLeft >= 5 ? "#dadada" : "error.main",
                        color: minutesLeft >= 5 ? undefined : "error.main",
                        fontSize: "0.9rem",
                        fontWeight: minutesLeft >= 5 ? undefined : 600,
                    },
                    onClick: () => updateLastAlive(),
                }}
            />
        </Box>
    );
};

export default HeaderSessionExpiresIn;
