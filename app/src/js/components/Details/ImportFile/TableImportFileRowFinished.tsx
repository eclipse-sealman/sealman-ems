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
import List, { ListProps } from "~app/components/Crud/List";
import { ImportFileInterface } from "~app/entities/ImportFile/definitions";
import getColumns from "~app/entities/ImportFileRow/finishedColumns";

interface TableImportFileRowFinishedProps extends Optional<ListProps, "endpointPrefix" | "columns"> {
    importFile: ImportFileInterface;
}

const TableImportFileRowFinished = ({ importFile, ...props }: TableImportFileRowFinishedProps) => {
    const columns = getColumns();

    return (
        <List
            {...{
                endpointPrefix: "/importfilerow",
                columns,
                ...props,
                defaultSorting: {
                    rowKey: "asc",
                    ...(props.defaultSorting ?? {}),
                },
                additionalFilters: {
                    importFile: {
                        filterBy: "importFile",
                        filterType: "equal",
                        filterValue: importFile.id,
                    },
                    ...(props.additionalFilters ?? {}),
                },
            }}
        />
    );
};

export default TableImportFileRowFinished;
export { TableImportFileRowFinishedProps };
