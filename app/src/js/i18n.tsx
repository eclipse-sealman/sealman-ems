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

import i18n from "i18next";
import LanguageDetector from "i18next-browser-languagedetector";
import { initReactI18next } from "react-i18next";
import en from "~translations/messages.en.json";

i18n.use(LanguageDetector)
    .use(initReactI18next)
    .init({
        resources: {
            en: {
                translations: en,
            },
        },
        fallbackLng: "en",
        debug: process.env.NODE_ENV === "development" ? true : false,
        ns: ["translations"],
        defaultNS: "translations",
        returnNull: false,
        interpolation: {
            escapeValue: false,
        },
    });

export default i18n;
