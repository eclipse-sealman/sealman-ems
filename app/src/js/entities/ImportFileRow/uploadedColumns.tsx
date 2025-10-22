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
import { getColumns, RepresentationColumn, TextColumn } from "@arteneo/forge";
import ImportFileRowParseStatusColumn from "~app/entities/ImportFileRow/columns/ImportFileRowParseStatusColumn";
import TextSqueezedCopyColumn from "~app/components/Table/columns/TextSqueezedCopyColumn";
import ImportFileRowTemplateColumn from "~app/entities/ImportFileRow/columns/ImportFileRowTemplateColumn";
import ImportFileRowAccessTagsColumn from "~app/entities/ImportFileRow/columns/ImportFileRowAccessTagsColumn";
import ImportFileRowLabelsColumn from "~app/entities/ImportFileRow/columns/ImportFileRowLabelsColumn";
import ImportFileRowReinstallConfig1Column from "~app/entities/ImportFileRow/columns/ImportFileRowReinstallConfig1Column";
import ImportFileRowReinstallConfig2Column from "~app/entities/ImportFileRow/columns/ImportFileRowReinstallConfig2Column";
import ImportFileRowReinstallConfig3Column from "~app/entities/ImportFileRow/columns/ImportFileRowReinstallConfig3Column";
import ImportFileRowEnabledColumn from "~app/entities/ImportFileRow/columns/ImportFileRowEnabledColumn";
import VariablesColumn from "~app/components/Table/columns/VariablesColumn";

const columns = {
    rowKey: <TextColumn />,
    parseStatus: <ImportFileRowParseStatusColumn />,
    deviceType: <RepresentationColumn />,
    name: <TextColumn />,
    serialNumber: <TextColumn />,
    imsi: <TextColumn />,
    model: <TextColumn />,
    registrationId: <TextSqueezedCopyColumn />,
    endorsementKey: <TextSqueezedCopyColumn />,
    hardwareVersion: <TextColumn />,
    template: <ImportFileRowTemplateColumn />,
    accessTags: <ImportFileRowAccessTagsColumn disableSorting />,
    labels: <ImportFileRowLabelsColumn disableSorting />,
    variables: <VariablesColumn disableSorting />,
    reinstallConfig1: <ImportFileRowReinstallConfig1Column />,
    reinstallConfig2: <ImportFileRowReinstallConfig2Column />,
    reinstallConfig3: <ImportFileRowReinstallConfig3Column />,
    enabled: <ImportFileRowEnabledColumn />,
};

export default getColumns(columns);
