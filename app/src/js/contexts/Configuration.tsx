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
import { Helmet } from "react-helmet";
import { useTranslation } from "react-i18next";
import { Box, CircularProgress, CssBaseline, ThemeProvider } from "@mui/material";
import axios, { AxiosError, AxiosResponse } from "axios";
import theme from "~app/theme";
import favicon from "~images/favicon.png";
import appleTouchIcon from "~images/apple-touch-icon.png";
import ErrorContent from "~app/components/ErrorContent";
import { MicrosoftOidcCredentialType, SingleSignOnType } from "~app/entities/Configuration/enums";

interface ConfigurationContextInterface {
    maintenanceMode: boolean;
    configGeneratorPhp: boolean;
    configGeneratorTwig: boolean;
    isTotpSecretGenerated: boolean;
    singleSignOn: SingleSignOnType;
    microsoftOidcCredential: MicrosoftOidcCredentialType;
    microsoftOidcUploadedCertificatePublicValidTo?: string;
    microsoftOidcGeneratedCertificatePublicValidTo?: string;
    isTotpEnabled: boolean;
    isRadiusEnabled: boolean;
    isScepAvailable: boolean;
    isVpnAvailable: boolean;
    disableAdminRestApiDocumentation: boolean;
    disableSmartemsRestApiDocumentation: boolean;
    disableVpnSecuritySuiteRestApiDocumentation: boolean;
}

interface ConfigurationInterface extends ConfigurationContextInterface {
    passwordBlockReuseOldPasswordCount: number;
    passwordMinimumLength: number;
    passwordDigitRequired: boolean;
    passwordBigSmallCharRequired: boolean;
    passwordSpecialCharRequired: boolean;
}

interface ConfigurationContextProps extends ConfigurationContextInterface {
    passwordComplexityHelp: React.ReactNode;
    reload: () => void;
}

interface ConfigurationProviderProps {
    children: React.ReactNode;
}

const contextInitial = {
    sessionTimeout: 900,
    maintenanceMode: false,
    configGeneratorPhp: false,
    configGeneratorTwig: false,
    isTotpSecretGenerated: false,
    singleSignOn: "disabled" as SingleSignOnType,
    microsoftOidcCredential: "clientSecret" as MicrosoftOidcCredentialType,
    isTotpEnabled: false,
    isRadiusEnabled: false,
    isScepAvailable: false,
    isVpnAvailable: false,
    disableAdminRestApiDocumentation: false,
    disableSmartemsRestApiDocumentation: false,
    disableVpnSecuritySuiteRestApiDocumentation: false,
    passwordComplexityHelp: "",
    reload: () => {
        // tslint:disable:no-empty
    },
};

const ConfigurationContext = React.createContext<ConfigurationContextProps>(contextInitial);

const ConfigurationProvider = ({ children }: ConfigurationProviderProps) => {
    const { t } = useTranslation();

    const [error, setError] = React.useState<undefined | number>(undefined);
    const [loading, setLoading] = React.useState(true);
    const [configuration, setConfiguration] = React.useState<undefined | ConfigurationInterface>(undefined);

    React.useEffect(() => load(), []);

    const fallbackConfiguration: ConfigurationInterface = {
        maintenanceMode: false,
        configGeneratorPhp: false,
        configGeneratorTwig: false,
        isTotpSecretGenerated: false,
        singleSignOn: "disabled",
        microsoftOidcCredential: "clientSecret",
        isTotpEnabled: false,
        isRadiusEnabled: false,
        isScepAvailable: false,
        isVpnAvailable: false,
        disableAdminRestApiDocumentation: false,
        disableSmartemsRestApiDocumentation: false,
        disableVpnSecuritySuiteRestApiDocumentation: false,
        passwordBlockReuseOldPasswordCount: 0,
        passwordMinimumLength: 8,
        passwordDigitRequired: false,
        passwordBigSmallCharRequired: false,
        passwordSpecialCharRequired: false,
    };

    const load = () => {
        setLoading(true);
        setError(undefined);

        // Different instance needs to be used to skip existing interceptors
        const axiosInstance = axios.create();
        axiosInstance
            .get("/anonymous/configuration")
            .then((response: AxiosResponse) => {
                const responseConfiguration: ConfigurationInterface = response.data;
                const configuration = Object.assign(fallbackConfiguration, responseConfiguration);

                setConfiguration(configuration);
                setLoading(false);
            })
            .catch((error: AxiosError) => {
                setLoading(false);
                setError(error?.response?.status ?? 500);
            });
    };

    const getPasswordComplexityHelp = () => {
        if (typeof configuration === "undefined") {
            return null;
        }

        const {
            passwordBlockReuseOldPasswordCount,
            passwordMinimumLength,
            passwordDigitRequired,
            passwordBigSmallCharRequired,
            passwordSpecialCharRequired,
        } = configuration;

        const complexityParts: string[] = [];

        if (passwordBlockReuseOldPasswordCount > 0) {
            complexityParts.push(
                t("passwordComplexity.passwordBlockReuseOldPasswordCount", {
                    count: passwordBlockReuseOldPasswordCount,
                })
            );
        }

        // Password with minimum length = 1 is not really a requirement worth noting
        if (passwordMinimumLength > 1) {
            complexityParts.push(t("passwordComplexity.passwordMinimumLength", { count: passwordMinimumLength }));
        }

        if (passwordDigitRequired) {
            complexityParts.push(t("passwordComplexity.passwordDigitRequired"));
        }

        if (passwordBigSmallCharRequired) {
            complexityParts.push(t("passwordComplexity.passwordBigSmallCharRequired"));
        }

        if (passwordSpecialCharRequired) {
            complexityParts.push(t("passwordComplexity.passwordSpecialCharRequired"));
        }

        if (complexityParts.length > 0) {
            return (
                <>
                    {t("passwordComplexity.general")}
                    {/* Help is inside <p> tag that is why we cannot use <ul> */}
                    <Box {...{ component: "span", sx: { display: "block", pl: "1.5em", listStyleType: "disc" } }}>
                        {complexityParts.map((complexityPart, key) => (
                            <Box key={key} {...{ component: "span", sx: { display: "list-item" } }}>
                                {complexityPart}
                            </Box>
                        ))}
                    </Box>
                </>
            );
        }
    };

    if (loading) {
        return (
            <>
                <Helmet>
                    <title>{t("common.loading")}</title>
                </Helmet>
                <Box
                    {...{ sx: { display: "flex", minHeight: "100vh", alignItems: "center", justifyContent: "center" } }}
                >
                    <CircularProgress />
                </Box>
            </>
        );
    }

    if (typeof error !== "undefined") {
        return (
            <>
                <Helmet>
                    <title>{t("meta.title")}</title>
                    <link {...{ rel: "icon", href: favicon }} />
                    <link {...{ rel: "apple-touch-icon", href: appleTouchIcon }} />
                </Helmet>
                <ThemeProvider theme={theme}>
                    <CssBaseline />
                    <ErrorContent {...{ error }} />
                </ThemeProvider>
            </>
        );
    }

    if (typeof configuration === "undefined") {
        return null;
    }

    return (
        <ConfigurationContext.Provider
            {...{
                value: {
                    reload: () => load(),
                    maintenanceMode: configuration.maintenanceMode,
                    configGeneratorPhp: configuration.configGeneratorPhp,
                    configGeneratorTwig: configuration.configGeneratorTwig,
                    isTotpSecretGenerated: configuration.isTotpSecretGenerated,
                    singleSignOn: configuration.singleSignOn,
                    microsoftOidcCredential: configuration.microsoftOidcCredential,
                    microsoftOidcUploadedCertificatePublicValidTo:
                        configuration.microsoftOidcUploadedCertificatePublicValidTo,
                    microsoftOidcGeneratedCertificatePublicValidTo:
                        configuration.microsoftOidcGeneratedCertificatePublicValidTo,
                    isTotpEnabled: configuration.isTotpEnabled,
                    isRadiusEnabled: configuration.isRadiusEnabled,
                    isVpnAvailable: configuration.isVpnAvailable,
                    isScepAvailable: configuration.isScepAvailable,
                    disableAdminRestApiDocumentation: configuration.disableAdminRestApiDocumentation,
                    disableSmartemsRestApiDocumentation: configuration.disableSmartemsRestApiDocumentation,
                    disableVpnSecuritySuiteRestApiDocumentation:
                        configuration.disableVpnSecuritySuiteRestApiDocumentation,
                    passwordComplexityHelp: getPasswordComplexityHelp(),
                },
            }}
        >
            <Helmet>
                <title>{t("meta.title")}</title>
                <link {...{ rel: "icon", href: favicon }} />
                <link {...{ rel: "apple-touch-icon", href: appleTouchIcon }} />
            </Helmet>
            {children}
        </ConfigurationContext.Provider>
    );
};

const useConfiguration = (): ConfigurationContextProps => React.useContext(ConfigurationContext);

export {
    ConfigurationContext,
    ConfigurationContextProps,
    ConfigurationProvider,
    ConfigurationProviderProps,
    useConfiguration,
    ConfigurationInterface,
    ConfigurationContextInterface,
};
