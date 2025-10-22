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
import { UploadSingleInputFile } from "@arteneo/forge-uppy";
import { uppyTusOptions } from "~app/utilities/uppy";
import { getFields } from "@arteneo/forge";
import PasswordWithObfuscatedValue from "~app/components/Form/fields/PasswordWithObfuscatedValue";

const fields = {
    pkcs12: <UploadSingleInputFile {...{ uppyTusOptions }} />,
    password: <PasswordWithObfuscatedValue />,
};

export default getFields(fields);
