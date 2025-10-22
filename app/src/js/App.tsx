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
import { BrowserRouter } from "react-router-dom";
import { CssBaseline, Slide, ThemeProvider } from "@mui/material";
import { LocalizationProvider } from "@mui/x-date-pickers";
import { AdapterDateFns } from "@mui/x-date-pickers/AdapterDateFns";
import {
    ErrorContext,
    ErrorContextProps,
    ErrorProvider,
    HandleCatchProvider,
    LoaderProvider,
    TableQueryProvider,
    SnackbarProvider,
    RequestExecutionErrorDialog,
} from "@arteneo/forge";
import axios from "axios";
import "@fontsource/source-sans-pro/400.css";
import "@fontsource/source-sans-pro/600.css";
import theme from "~app/theme";
import Error from "~app/components/Error";
import AppRoutes from "~app/AppRoutes";
import "./i18n";
import { UserProvider } from "~app/contexts/User";
import { SidebarProvider } from "~app/contexts/Sidebar";
import { ConfigurationProvider } from "~app/contexts/Configuration";
import { applyAuthenticationInterceptor, applyAxiosBaseUrl } from "~app/utilities/authentication";

applyAuthenticationInterceptor(axios);
applyAxiosBaseUrl(axios, process.env.API_URL_PREFIX);

const App = () => {
    return (
        <LocalizationProvider
            dateAdapter={AdapterDateFns}
            dateFormats={{ fullDate: "dd-MM-yyyy", fullDateTime24h: "dd-MM-yyyy HH:mm", fullTime24h: "HH:mm" }}
        >
            <ConfigurationProvider>
                <ThemeProvider theme={theme}>
                    <CssBaseline />
                    <BrowserRouter>
                        <ErrorProvider>
                            <HandleCatchProvider
                                mode={process.env.NODE_ENV === "production" ? "production" : "development"}
                            >
                                <LoaderProvider>
                                    <UserProvider>
                                        <SidebarProvider>
                                            <SnackbarProvider {...{ snackbarProps: { TransitionComponent: Slide } }}>
                                                <TableQueryProvider>
                                                    <ErrorContext.Consumer>
                                                        {({ error }: ErrorContextProps) =>
                                                            error && error !== 409 ? <Error /> : <AppRoutes />
                                                        }
                                                    </ErrorContext.Consumer>
                                                </TableQueryProvider>
                                            </SnackbarProvider>
                                        </SidebarProvider>
                                    </UserProvider>
                                    <RequestExecutionErrorDialog />
                                </LoaderProvider>
                            </HandleCatchProvider>
                        </ErrorProvider>
                    </BrowserRouter>
                </ThemeProvider>
            </ConfigurationProvider>
        </LocalizationProvider>
    );
};

export default App;
