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
import { useLocation } from "react-router-dom";

interface DetailsContextProps {
    reloadCounter: number;
    reload: () => void;
}

interface DetailsProviderProps {
    children: React.ReactNode;
    disableReloadOnLocationKeyChange?: boolean;
}

const contextInitial = {
    reloadCounter: 0,
    reload: () => {
        // tslint:disable:no-empty
    },
};

const DetailsContext = React.createContext<DetailsContextProps>(contextInitial);

const DetailsProvider = ({ children, disableReloadOnLocationKeyChange = false }: DetailsProviderProps) => {
    const location = useLocation();

    const didMount = React.useRef(false);
    const [reloadCounter, setReloadCounter] = React.useState(0);

    React.useEffect(() => {
        // Do not reload on initial render
        if (didMount?.current) {
            reload();
        }

        didMount.current = true;
    }, [disableReloadOnLocationKeyChange ? disableReloadOnLocationKeyChange : location.key]);

    const reload = () => {
        setReloadCounter((reloadCounter) => reloadCounter + 1);
    };

    return (
        <DetailsContext.Provider
            value={{
                reloadCounter,
                reload,
            }}
        >
            {children}
        </DetailsContext.Provider>
    );
};

const useDetails = (): DetailsContextProps => React.useContext(DetailsContext);

export { DetailsContext, DetailsContextProps, DetailsProvider, DetailsProviderProps, useDetails };
