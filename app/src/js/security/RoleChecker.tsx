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
import { Navigate, Outlet } from "react-router-dom";
import { RoleCheckerProps, useUser } from "~app/contexts/User";

const RoleChecker = (props: RoleCheckerProps) => {
    const { isAccessGranted } = useUser();
    return isAccessGranted(props) ? <Outlet /> : <Navigate {...{ to: "/authentication/login" }} />;
};

export default RoleChecker;
