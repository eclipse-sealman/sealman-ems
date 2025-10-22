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
import {
    BooleanColumn,
    CollectionRepresentationColumn,
    getColumns,
    RepresentationColumn,
    TextColumn,
} from "@arteneo/forge";
import ImportFileRowImportStatusColumn from "~app/entities/ImportFileRow/columns/ImportFileRowImportStatusColumn";
import TextSqueezedCopyColumn from "~app/components/Table/columns/TextSqueezedCopyColumn";
import VariablesColumn from "~app/components/Table/columns/VariablesColumn";

const columns = {
    rowKey: <TextColumn />,
    importStatus: <ImportFileRowImportStatusColumn />,
    deviceType: <RepresentationColumn />,
    name: <TextColumn />,
    serialNumber: <TextColumn />,
    imsi: <TextColumn />,
    model: <TextColumn />,
    registrationId: <TextSqueezedCopyColumn />,
    endorsementKey: <TextSqueezedCopyColumn />,
    hardwareVersion: <TextColumn />,
    template: <RepresentationColumn />,
    accessTags: <CollectionRepresentationColumn disableSorting />,
    labels: <CollectionRepresentationColumn disableSorting />,
    variables: <VariablesColumn disableSorting />,
    reinstallConfig1: <BooleanColumn />,
    reinstallConfig2: <BooleanColumn />,
    reinstallConfig3: <BooleanColumn />,
    enabled: <BooleanColumn />,
};

export default getColumns(columns);
