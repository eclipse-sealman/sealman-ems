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
import { ColumnPathInterface, ResultInterface } from "@arteneo/forge";
import ConfigShow from "~app/entities/Config/actions/ConfigShow";
import ConfigEdit from "~app/entities/Config/actions/ConfigEdit";

const ConfigShowEditColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("ConfigShowEditColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("ConfigShowEditColumn component: Missing required result prop");
    }

    const config: ResultInterface = getIn(result, path ? path : columnName);
    if (!config) {
        return null;
    }

    return (
        <>
            {config.representation}
            <ConfigShow
                {...{ result: config, size: "small", color: "success", sx: { ml: 1, py: 0, px: 1.25, fontSize: 13 } }}
            />
            <ConfigEdit {...{ result: config, size: "small", sx: { ml: 1, py: 0, px: 1.25, fontSize: 13 } }} />
        </>
    );
};

export default ConfigShowEditColumn;
export { ColumnPathInterface as ConfigShowEditColumnProps };
