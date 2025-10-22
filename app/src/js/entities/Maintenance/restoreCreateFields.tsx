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
import { Checkbox, getFields, SelectApi } from "@arteneo/forge";
import PasswordWithObfuscatedValue from "~app/components/Form/fields/PasswordWithObfuscatedValue";

const fields = {
    filepath: (
        <SelectApi
            {...{ endpoint: "/maintenance/restore/archive/file/paths", label: "restoreFilepath", required: true }}
        />
    ),
    restoreDatabase: <Checkbox />,
    restoreFilestorage: <Checkbox />,
    restorePassword: <PasswordWithObfuscatedValue />,
};

export default getFields(fields);
