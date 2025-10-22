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
import { Table as ForgeTable, TableProps } from "@arteneo/forge";
import TableContent from "~app/components/Table/components/TableContent";

const Table = (props: TableProps) => {
    return (
        <ForgeTable {...props}>
            <TableContent />
        </ForgeTable>
    );
};

export default Table;
export { TableProps };
