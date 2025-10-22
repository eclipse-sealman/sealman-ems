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

import UnitInterface from "~app/definitions/UnitInterface";

export function formatIpSortable(ipSortable?: number): undefined | string {
    if (typeof ipSortable === "undefined") {
        return undefined;
    }

    if (isNaN(ipSortable)) {
        return undefined;
    }

    // ipSortable > 255.255.255.255 or ipSortable < 0.0.0.0
    if (ipSortable > 4294967295 || ipSortable < 0) {
        return undefined;
    }

    return [ipSortable >>> 24, (ipSortable >>> 16) & 0xff, (ipSortable >>> 8) & 0xff, ipSortable & 0xff].join(".");
}

export function formatBytes(bytes: number) {
    const units = ["bytes", "KiB", "MiB", "GiB", "TiB", "PiB", "EiB", "ZiB", "YiB"];

    let l = 0;
    let n = bytes;

    while (n >= 1024 && ++l) {
        n = n / 1024;
    }

    return n.toFixed(n < 10 && l > 0 ? 1 : 0) + " " + units[l];
}

export const uptimeUnits: UnitInterface[] = [
    {
        value: 1,
        label: "units.uptime.second",
    },
    {
        value: 60,
        label: "units.uptime.minute",
    },
    {
        value: 3600,
        label: "units.uptime.hour",
    },
    {
        value: 86400,
        label: "units.uptime.day",
    },
];

export const uptimePluralUnits: UnitInterface[] = [
    {
        value: 1,
        label: "units.uptimePlural.second",
    },
    {
        value: 60,
        label: "units.uptimePlural.minute",
    },
    {
        value: 3600,
        label: "units.uptimePlural.hour",
    },
    {
        value: 86400,
        label: "units.uptimePlural.day",
    },
];

export const uptimeShortUnits: UnitInterface[] = [
    {
        value: 1,
        label: "units.uptimeShort.second",
    },
    {
        value: 60,
        label: "units.uptimeShort.minute",
    },
    {
        value: 3600,
        label: "units.uptimeShort.hour",
    },
    {
        value: 86400,
        label: "units.uptimeShort.day",
    },
];

export function formatUptime(uptimeSeconds: number) {
    return formatUnits(uptimeSeconds, uptimeUnits);
}

export function formatUptimeShort(uptimeSeconds: number) {
    return formatUnits(uptimeSeconds, uptimeShortUnits);
}

export function formatUnits(value: number, units: UnitInterface[]) {
    const formattedParts: UnitInterface[] = [];

    // Slice is used so original array will not be mutated
    units
        .slice()
        .reverse()
        .forEach((unit) => {
            const divided = Math.floor(value / unit.value);
            if (divided > 0) {
                formattedParts.push({
                    value: divided,
                    label: unit.label,
                });

                value -= divided * unit.value;
            }
        });

    return formattedParts;
}
