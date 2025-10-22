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

interface SidebarContextProps {
    expanded: boolean;
    setExpanded: React.Dispatch<React.SetStateAction<boolean>>;
    mobileOpen: boolean;
    setMobileOpen: React.Dispatch<React.SetStateAction<boolean>>;
}

interface SidebarProviderProps {
    children: React.ReactNode;
}

const contextInitial = {
    expanded: true,
    setExpanded: () => {
        // tslint:disable:no-empty
    },
    mobileOpen: true,
    setMobileOpen: () => {
        // tslint:disable:no-empty
    },
};

const SidebarContext = React.createContext<SidebarContextProps>(contextInitial);

const SidebarProvider = ({ children }: SidebarProviderProps) => {
    const [expanded, setExpanded] = React.useState(true);
    const [mobileOpen, setMobileOpen] = React.useState(false);

    return (
        <SidebarContext.Provider
            value={{
                expanded,
                setExpanded,
                mobileOpen,
                setMobileOpen,
            }}
        >
            {children}
        </SidebarContext.Provider>
    );
};

const useSidebar = (): SidebarContextProps => React.useContext(SidebarContext);

export { SidebarContext, SidebarContextProps, SidebarProvider, SidebarProviderProps, useSidebar };
