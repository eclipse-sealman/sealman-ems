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

import { Enum, EnumType } from "@arteneo/forge";

class EnumIntegerValid extends Enum {
    // Override isValid method to accept enumName as integer which is returned from backend
    isValid(enumName: EnumType): boolean {
        return this.enums.includes(enumName.toString());
    }
}

const cidr = new EnumIntegerValid(
    [
        "32",
        "31",
        "30",
        "29",
        "28",
        "27",
        "26",
        "25",
        "24",
        "23",
        "22",
        "21",
        "20",
        "19",
        "18",
        "17",
        "16",
        "15",
        "14",
        "13",
        "12",
        "11",
        "10",
        "9",
        "8",
    ],
    "enum.common.cidr."
);

export { cidr };
