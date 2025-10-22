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
import { getIn } from "formik";
import { ColumnPathInterface } from "@arteneo/forge";
import { useTranslation } from "react-i18next";
import { masqueradeType as masqueradeTypeEnum } from "~app/enums/MasqueradeType";
import { TemplateVersionMasqueradeInterface } from "~app/entities/TemplateVersion/definitions";

interface MasqueradeTypeColumnProps extends ColumnPathInterface {
    masqueradeTypePath?: string;
    masqueradesPath?: string;
}

const MasqueradeTypeColumn = ({
    masqueradeTypePath = "masqueradeType",
    masqueradesPath = "masquerades",
    result,
    columnName,
}: MasqueradeTypeColumnProps) => {
    const { t } = useTranslation();

    if (typeof columnName === "undefined") {
        throw new Error("MasqueradeTypeColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("MasqueradeTypeColumn component: Missing required result prop");
    }

    const masqueradeType = getIn(result, masqueradeTypePath);
    const masquerades: TemplateVersionMasqueradeInterface[] = getIn(result, masqueradesPath, []);

    return (
        <>
            {t(masqueradeTypeEnum.getLabel(masqueradeType))}
            {masquerades.length > 0 && <> ({masquerades.map((masquerade) => masquerade.subnet).join(", ")})</>}
        </>
    );
};

export default MasqueradeTypeColumn;
export { MasqueradeTypeColumnProps };
