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
import { getFields } from "@arteneo/forge";
import PasswordComplexityHelp from "~app/components/Form/fields/PasswordComplexityHelp";
import PasswordWithObfuscatedValue from "~app/components/Form/fields/PasswordWithObfuscatedValue";

const fields = {
    currentPlainPassword: <PasswordWithObfuscatedValue {...{ required: true }} />,
    newPlainPassword: <PasswordComplexityHelp {...{ required: true }} />,
    newPlainPasswordRepeat: <PasswordWithObfuscatedValue {...{ required: true }} />,
};

export default getFields(fields);
