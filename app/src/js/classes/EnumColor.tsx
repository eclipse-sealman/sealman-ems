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

type EnumColorType = "default" | "primary" | "secondary" | "critical" | "error" | "info" | "success" | "warning";

class EnumColor extends Enum {
    colors: EnumColorType[];

    constructor(enums: EnumType[], colors: EnumColorType[], prefix: string, invalidLabel = "enum.invalid") {
        super(enums, prefix, invalidLabel);

        this.colors = colors;
    }

    getColors(): EnumColorType[] {
        return this.colors;
    }

    getColor(enumName: EnumType): undefined | EnumColorType {
        if (!this.isValid(enumName)) {
            return undefined;
        }

        const enumIndex = this.enums.indexOf(enumName);
        return this.colors[enumIndex];
    }
}

export default EnumColor;
export { EnumColorType };
