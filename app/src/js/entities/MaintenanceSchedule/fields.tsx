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
import { OptionInterface, Checkbox, getFields, Select, Text } from "@arteneo/forge";
import SelectCronSchedule from "~app/components/Form/fields/SelectCronSchedule";
import PasswordWithObfuscatedValue from "~app/components/Form/fields/PasswordWithObfuscatedValue";

const dayOfWeekOptions: OptionInterface[] = [
    { id: -1, representation: "selectCronDayOfWeek.any" },
    { id: 1, representation: "selectCronDayOfWeek.monday" },
    { id: 2, representation: "selectCronDayOfWeek.tuesday" },
    { id: 3, representation: "selectCronDayOfWeek.wednesday" },
    { id: 4, representation: "selectCronDayOfWeek.thursday" },
    { id: 5, representation: "selectCronDayOfWeek.friday" },
    { id: 6, representation: "selectCronDayOfWeek.saturday" },
    { id: 7, representation: "selectCronDayOfWeek.sunday" },
];

const fields = {
    name: <Text {...{ required: true }} />,
    backupDatabase: <Checkbox />,
    backupFilestorage: <Checkbox />,
    backupPassword: <PasswordWithObfuscatedValue />,
    dayOfMonth: <SelectCronSchedule {...{ required: true, startValue: 1, endValue: 31 }} />,
    dayOfWeek: <Select {...{ required: true, options: dayOfWeekOptions }} />,
    hour: <SelectCronSchedule {...{ required: true, startValue: 0, endValue: 23 }} />,
    minute: <SelectCronSchedule {...{ required: true, startValue: 0, endValue: 59, step: 5 }} />,
};

export default getFields(fields);
export { dayOfWeekOptions };
