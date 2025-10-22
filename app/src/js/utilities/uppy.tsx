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

import { TusOptions } from "@uppy/tus";
import { DetailedError } from "tus-js-client";
import { getAccessToken, refreshAccessToken, updateLastAlive } from "~app/utilities/authentication";

export const uppyTusOptions: TusOptions = {
    endpoint: "/web/tus/upload",
    async onBeforeRequest(req) {
        const accessToken = await getAccessToken();
        req.setHeader("Authorization", "Bearer " + accessToken);
    },
    onShouldRetry(err, retryAttempt, options, next) {
        if ((err as DetailedError)?.originalResponse?.getStatus() === 401) {
            return true;
        }

        return next(err);
    },
    async onAfterResponse(req, res) {
        if (res.getStatus() === 401) {
            await refreshAccessToken();
        }

        updateLastAlive();
    },
};

export const uppyImageOptions = {
    // Restrictions consistent with backend constraints
    restrictions: {
        maxFileSize: 5 * 1024 * 1024,
        allowedFileTypes: ["image/*"],
    },
};
