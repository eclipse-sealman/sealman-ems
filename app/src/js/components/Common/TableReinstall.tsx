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
import { Optional } from "@arteneo/forge";
import Table, { TableProps } from "~app/components/Table/components/Table";
import getColumns from "~app/entities/Device/reinstallColumns";

type TableReinstallProps = Optional<TableProps, "endpoint" | "columns">;

const TableReinstall = (props: TableReinstallProps) => {
    const columns = getColumns();

    return (
        <Table
            {...{
                endpoint: "/device/list",
                columns,
                ...props,
            }}
        />
    );
};

export default TableReinstall;
export { TableReinstallProps };
