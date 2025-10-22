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

import EntityDenyInterface from "~app/definitions/EntityDenyInterface";
import EntityInterface from "~app/definitions/EntityInterface";
import { StatusType } from "~app/entities/ImportFile/enums";

interface ImportFileInterface extends EntityDenyInterface {
    status: StatusType;
    filename: string;
    filepath: string;
    updatedAt?: string;
    updatedBy?: EntityInterface;
    createdAt?: string;
    createdBy?: EntityInterface;
}

export { ImportFileInterface };
