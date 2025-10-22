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
import { ColumnActionInterface } from "@arteneo/forge";
import ButtonExpand, { ButtonExpandProps } from "~app/components/Common/ButtonExpand";

type ResultButtonExpandProps = ButtonExpandProps & ColumnActionInterface;

const ResultButtonExpand = ({ result, ...props }: ResultButtonExpandProps) => {
    return <ButtonExpand {...{ childrenProps: { result }, ...props }} />;
};

export default ResultButtonExpand;
export { ResultButtonExpandProps };
