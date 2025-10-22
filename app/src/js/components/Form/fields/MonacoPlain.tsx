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
import Monaco, { MonacoProps } from "~app/components/Form/fields/Monaco";

const MonacoPlain = (props: MonacoProps) => {
    return <Monaco {...{ disableActionFormat: true, languageInformation: "Plain", ...props }} />;
};

export default MonacoPlain;
export { MonacoProps as MonacoPlainProps };
