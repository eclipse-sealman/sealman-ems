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
import { FieldsInterface, renderField } from "@arteneo/forge";
import { Alert, Box, Typography } from "@mui/material";
import CrudFormView, { CrudFormViewProps } from "~app/views/CrudFormView";
import { useTranslation } from "react-i18next";

interface DeviceTypeLimitedFieldsetProps extends Omit<CrudFormViewProps, "children"> {
    fields: FieldsInterface;
    noCommunicationFields?: boolean;
}

const DeviceTypeLimitedFieldset = ({
    fields,
    noCommunicationFields = false,
    ...formViewProps
}: DeviceTypeLimitedFieldsetProps) => {
    const { t } = useTranslation();
    const render = renderField(fields);

    return (
        <CrudFormView {...formViewProps}>
            <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                <Box>
                    <Alert severity="info" {...{ sx: { mb: 2 } }}>
                        {t("configuration.deviceType.limitedEditInfo")}
                    </Alert>
                    <Typography {...{ variant: "h3", sx: { mb: 2 } }}>
                        {t("configuration.deviceType.general")}
                    </Typography>
                    <Box
                        {...{
                            sx: {
                                display: "flex",
                                flexDirection: "column",
                                gap: 3,
                                ml: 1,
                                pl: 2,
                                borderLeftWidth: 1,
                                borderLeftStyle: "dashed",
                                borderLeftColor: "grey.400",
                            },
                        }}
                    >
                        {render("name")}
                        <Box
                            {...{
                                sx: {
                                    display: "grid",
                                    gridTemplateColumns: { xs: "minmax(0, 1fr)", md: "repeat(2, minmax(0,1fr))" },
                                    gap: 2,
                                },
                            }}
                        >
                            {render("deviceName")}
                            {render("certificateCommonNamePrefix")}
                            {render("icon")}
                            {render("color")}
                        </Box>
                        {!noCommunicationFields && render("enableConnectionAggregation")}
                        {!noCommunicationFields && render("connectionAggregationPeriod")}

                        {!noCommunicationFields && render("authenticationMethod")}
                        {!noCommunicationFields && render("credentialsSource")}
                        {!noCommunicationFields && render("deviceTypeSecretCredential")}
                        {!noCommunicationFields && render("deviceTypeCertificateTypeCredential")}
                    </Box>
                </Box>
                {!noCommunicationFields && (
                    <Box>
                        <Typography {...{ variant: "h3", sx: { mb: 2 } }}>
                            {t("configuration.deviceType.deviceConfiguration")}
                        </Typography>
                        <Box
                            {...{
                                sx: {
                                    display: "flex",
                                    flexDirection: "column",
                                    gap: 3,
                                    ml: 1,
                                    pl: 2,
                                    borderLeftWidth: 1,
                                    borderLeftStyle: "dashed",
                                    borderLeftColor: "grey.400",
                                },
                            }}
                        >
                            {render("enableFirmwareMinRsrp")}
                            {render("firmwareMinRsrp")}
                            {render("enableConfigMinRsrp")}
                            {render("configMinRsrp")}
                            {!noCommunicationFields && render("enableConfigLogs")}
                        </Box>
                    </Box>
                )}

                <Box>
                    <Typography {...{ variant: "h3", sx: { mb: 2 } }}>
                        {t("configuration.deviceType.functionalities")}
                    </Typography>
                    <Box
                        {...{
                            sx: {
                                display: "grid",
                                gridTemplateColumns: { xs: "minmax(0, 1fr)", md: "repeat(3, minmax(0,1fr))" },
                                gap: 3,
                                ml: 1,
                                pl: 2,
                                borderLeftWidth: 1,
                                borderLeftStyle: "dashed",
                                borderLeftColor: "grey.400",
                            },
                        }}
                    >
                        <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                            {render("hasCertificates")}
                        </Box>
                        <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                            {render("hasCertificateTypesCollection")}
                        </Box>
                        <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                            {render("hasMasquerade")}
                            {render("masqueradeType")}
                        </Box>
                        <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                            {render("hasVpn")}
                            {render("hasEndpointDevices")}
                            {render("virtualSubnetCidr")}
                        </Box>
                        <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                            {render("hasDeviceCommands")}
                            {render("deviceCommandMaxRetries")}
                            {render("deviceCommandExpireDuration")}
                        </Box>
                    </Box>
                </Box>
                {render("certificateTypes")}
            </Box>
        </CrudFormView>
    );
};

export default DeviceTypeLimitedFieldset;
export { DeviceTypeLimitedFieldsetProps };
