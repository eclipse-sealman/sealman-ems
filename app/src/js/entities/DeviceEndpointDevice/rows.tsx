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
import { CollectionRepresentationColumn, TextColumn } from "@arteneo/forge";
import { getRows } from "~app/utilities/common";
import CreatedAtByLineColumn from "~app/components/Table/columns/CreatedAtByLineColumn";
import UpdatedAtByLineColumn from "~app/components/Table/columns/UpdatedAtByLineColumn";
import DateTimeSecondsColumn from "~app/components/Table/columns/DateTimeSecondsColumn";
import VirtualIpColumn from "~app/components/Table/columns/VirtualIpColumn";
import DeviceAndDeviceTypeColumn from "~app/components/Table/columns/DeviceAndDeviceTypeColumn";

const rows = {
    name: <TextColumn />,
    description: <TextColumn />,
    device: <DeviceAndDeviceTypeColumn />,
    physicalIp: <TextColumn />,
    virtualIp: <VirtualIpColumn {...{ virtualIpHostPartPath: "virtualIpHostPart" }} />,
    vpnLastConnectionAt: <DateTimeSecondsColumn />,
    accessTags: <CollectionRepresentationColumn />,
    updatedAtBy: <UpdatedAtByLineColumn />,
    createdAtBy: <CreatedAtByLineColumn />,
};

export default getRows(rows);
