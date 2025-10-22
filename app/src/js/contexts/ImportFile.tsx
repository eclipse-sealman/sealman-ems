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
import { OptionsType, useHandleCatch, useLoader } from "@arteneo/forge";
import axios from "axios";

interface ImportFileContextProps {
    templates: OptionsType;
    accessTags: OptionsType;
    labels: OptionsType;
    getDeviceTypeTemplates: (deviceTypeId: number) => OptionsType;
}

interface ImportFileProviderProps {
    children: React.ReactNode;
}

const contextInitial = {
    templates: [],
    accessTags: [],
    labels: [],
    getDeviceTypeTemplates: () => {
        return [];
    },
};

const ImportFileContext = React.createContext<ImportFileContextProps>(contextInitial);

const ImportFileProvider = ({ children }: ImportFileProviderProps) => {
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [templates, setTemplates] = React.useState<undefined | OptionsType>(undefined);
    const [accessTags, setAccessTags] = React.useState<undefined | OptionsType>(undefined);
    const [labels, setLabels] = React.useState<undefined | OptionsType>(undefined);

    React.useEffect(() => initialize(), []);

    const initialize = () => {
        showLoader();

        Promise.all([axios.get("/options/templates"), axios.get("/options/access/tags"), axios.get("/options/labels")])
            .then(([{ data: templates }, { data: accessTags }, { data: labels }]) => {
                setTemplates(templates);
                setAccessTags(accessTags);
                setLabels(labels);

                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });
    };

    const getDeviceTypeTemplates = (deviceTypeId: number): OptionsType => {
        if (typeof templates === "undefined") {
            return [];
        }

        return templates.filter((template) => template?.deviceType?.id === deviceTypeId);
    };

    if (typeof templates === "undefined" || typeof accessTags === "undefined" || typeof labels === "undefined") {
        return null;
    }

    return (
        <ImportFileContext.Provider
            value={{
                templates,
                getDeviceTypeTemplates,
                accessTags,
                labels,
            }}
        >
            {children}
        </ImportFileContext.Provider>
    );
};

const useImportFile = (): ImportFileContextProps => React.useContext(ImportFileContext);

export { ImportFileContext, ImportFileContextProps, ImportFileProvider, ImportFileProviderProps, useImportFile };
