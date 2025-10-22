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
import { useTranslation } from "react-i18next";
import UnitInterface from "~app/definitions/UnitInterface";

interface DisplayUnitsProps {
    units: UnitInterface[];
}

const DisplayUnits = ({ units }: DisplayUnitsProps) => {
    const { t } = useTranslation();

    return <>{units.map((unit) => unit.value + " " + t(unit.label, { count: unit.value })).join(" ")}</>;
};

export default DisplayUnits;
export { DisplayUnitsProps };
