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
import { BooleanColumn, DateTimeColumn, getColumns, TextColumn } from "@arteneo/forge";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";
import DayOfWeekColumn from "~app/entities/MaintenanceSchedule/columns/DayOfWeekColumn";
import SelectCronScheduleColumn from "~app/entities/MaintenanceSchedule/columns/SelectCronScheduleColumn";

const columns = {
    name: <TextColumn />,
    backupDatabase: <BooleanColumn />,
    backupFilestorage: <BooleanColumn />,
    dayOfMonth: <SelectCronScheduleColumn />,
    dayOfWeek: <DayOfWeekColumn />,
    hour: <SelectCronScheduleColumn />,
    minute: <SelectCronScheduleColumn />,
    nextJobAt: <DateTimeColumn />,
    actions: <BuilderActionsColumn />,
};

export default getColumns(columns);
