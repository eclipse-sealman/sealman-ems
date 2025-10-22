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
import { Box } from "@mui/material";
import { useTranslation } from "react-i18next";

const Footer = () => {
    const { t } = useTranslation();
    const year = new Date().getFullYear();

    return (
        <Box {...{ sx: { textAlign: "center", color: "#959595", fontSize: 14 } }}>
            {t("footer.copyright", { year: year.toString() })}
        </Box>
    );
};

export default Footer;
