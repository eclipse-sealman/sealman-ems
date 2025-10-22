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
import { useTranslation } from "react-i18next";

interface DeviceTypeFieldsetContentProps {
    fields: FieldsInterface;
    disableAll?: boolean;
    enableConfigMinRsrp: boolean;
    enableFirmwareMinRsrp: boolean;
    noCommunicationFields?: boolean;
}

const DeviceTypeFieldsetContent = ({
    fields,
    disableAll = false,
    enableConfigMinRsrp,
    enableFirmwareMinRsrp,
    noCommunicationFields = false,
}: DeviceTypeFieldsetContentProps) => {
    const { t } = useTranslation();

    const renderFunction = renderField(fields);
    const defaultProps = disableAll ? { disabled: true } : {};

    // eslint-disable-next-line
    const render = (field: string, props?: any) => renderFunction(field, Object.assign({}, props, defaultProps));

    return (
        <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
            <Box>
                <Typography {...{ variant: "h3", sx: { mb: 2 } }}>{t("configuration.deviceType.general")}</Typography>
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

                    {!noCommunicationFields && render("routePrefix")}
                    {!noCommunicationFields && render("authenticationMethod")}
                    {!noCommunicationFields && render("credentialsSource")}
                    {!noCommunicationFields && render("deviceTypeSecretCredential")}
                    {!noCommunicationFields && render("deviceTypeCertificateTypeCredential")}
                </Box>
            </Box>
            {(!noCommunicationFields || enableFirmwareMinRsrp || enableConfigMinRsrp) && (
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
                        {enableFirmwareMinRsrp && (
                            <Box
                                {...{
                                    sx: {
                                        display: "grid",
                                        gridTemplateColumns: { xs: "minmax(0, 1fr)", md: "repeat(3, minmax(0,1fr))" },
                                        gap: 3,
                                    },
                                }}
                            >
                                <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                                    {render("hasFirmware1")}
                                    {render("nameFirmware1")}
                                    {render("customUrlFirmware1")}
                                </Box>
                                <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                                    {render("hasFirmware2")}
                                    {render("nameFirmware2")}
                                    {render("customUrlFirmware2")}
                                </Box>
                                <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                                    {render("hasFirmware3")}
                                    {render("nameFirmware3")}
                                    {render("customUrlFirmware3")}
                                </Box>
                            </Box>
                        )}
                        {enableConfigMinRsrp && (
                            <Box
                                {...{
                                    sx: {
                                        display: "grid",
                                        gridTemplateColumns: { xs: "minmax(0, 1fr)", md: "repeat(3, minmax(0,1fr))" },
                                        gap: 3,
                                    },
                                }}
                            >
                                <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                                    {render("hasConfig1")}
                                    {render("nameConfig1")}
                                    {render("formatConfig1")}
                                    {render("hasAlwaysReinstallConfig1")}
                                </Box>
                                <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                                    {render("hasConfig2")}
                                    {render("nameConfig2")}
                                    {render("formatConfig2")}
                                    {render("hasAlwaysReinstallConfig2")}
                                </Box>
                                <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                                    {render("hasConfig3")}
                                    {render("nameConfig3")}
                                    {render("formatConfig3")}
                                    {render("hasAlwaysReinstallConfig3")}
                                </Box>
                            </Box>
                        )}
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
                    {render("hasTemplates")}
                    {render("hasGsm")}
                    {render("hasRequestConfig")}
                    {render("hasRequestDiagnose")}
                    <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                        {render("hasMasquerade")}
                        {render("masqueradeType")}
                    </Box>
                    {render("hasVariables")}
                    <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                        {render("hasCertificates")}
                    </Box>
                    {render("hasCertificateTypesCollection")}
                    {render("hasVpn")}
                    <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                        {render("hasEndpointDevices")}
                        {render("virtualSubnetCidr")}
                    </Box>
                    <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                        {render("hasDeviceCommands")}
                        {render("deviceCommandMaxRetries")}
                        {render("deviceCommandExpireDuration")}
                    </Box>
                    {render("hasDeviceToNetworkConnection")}
                </Box>
            </Box>
            {render("certificateTypes")}
            <Box>
                <Typography {...{ variant: "h3", sx: { mb: 2 } }}>
                    {t("configuration.deviceType.fieldRequirements")}
                </Typography>
                <Alert {...{ severity: "info", sx: { mb: 2 } }}>{t("help.fieldRequirement")}</Alert>
                <Box
                    {...{
                        sx: {
                            display: "grid",
                            gridTemplateColumns: { xs: "minmax(0, 1fr)" },
                            gap: 3,
                            ml: 1,
                            pl: 2,
                            borderLeftWidth: 1,
                            borderLeftStyle: "dashed",
                            borderLeftColor: "grey.400",
                        },
                    }}
                >
                    {render("fieldSerialNumber")}
                    {render("fieldImsi")}
                    {render("fieldModel")}
                    {render("fieldRegistrationId")}
                    {render("fieldEndorsementKey")}
                    {render("fieldHardwareVersion")}
                </Box>
            </Box>
        </Box>
    );
};

export default DeviceTypeFieldsetContent;
export { DeviceTypeFieldsetContentProps };
