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

import { DenyInterface } from "@arteneo/forge";
import { DisplayRowsInterface } from "~app/components/Display/Display";

export const pickRows = (
    names: undefined | string[],
    skipNames: undefined | string[],
    rows: DisplayRowsInterface
): DisplayRowsInterface => {
    const _rows: DisplayRowsInterface = {};
    const rowNames = typeof names === "undefined" ? Object.keys(rows) : names;

    rowNames.forEach((rowName) => {
        if (typeof rowName !== "string") {
            throw new Error("Row name " + rowName + " not supported");
        }
        if (typeof rows[rowName] === "undefined") {
            throw new Error("Row name " + rowName + " not supported");
        }
        if (typeof skipNames !== "undefined" && skipNames.includes(rowName)) {
            return;
        }

        _rows[rowName] = rows[rowName];
    });

    return _rows;
};

export const getRows = (rows: DisplayRowsInterface) => (names?: string[], skipNames?: string[]) =>
    pickRows(names, skipNames, rows);

export const isDenyHidden = (denyKey: string, endsWith: string, deny?: DenyInterface): boolean =>
    deny?.[denyKey]?.endsWith(endsWith) ? true : false;
