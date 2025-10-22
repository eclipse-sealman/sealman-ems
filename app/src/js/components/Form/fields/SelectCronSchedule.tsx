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
import { OptionInterface, Select, SelectProps } from "@arteneo/forge";
import { useTranslation } from "react-i18next";

interface SelectCronScheduleProps extends Omit<SelectProps, "options"> {
    startValue: number;
    endValue: number;
    step?: number;
}

const SelectCronSchedule = ({ startValue, endValue, step = 1, ...selectProps }: SelectCronScheduleProps) => {
    const { t } = useTranslation();

    const options: OptionInterface[] = [{ id: -1, representation: t("selectCronSchedule.any") }];

    for (let i = startValue; i <= endValue; i = i + step) {
        options.push({ id: i, representation: i.toString() });
    }

    return (
        <Select
            {...{
                options,
                disableTranslateOption: true,
                ...selectProps,
            }}
        />
    );
};

export default SelectCronSchedule;
export { SelectCronScheduleProps };
