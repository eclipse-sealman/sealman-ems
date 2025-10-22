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
import getColumns from "~app/entities/ImportFileRow/uploadedColumns";
import BatchButtonExpand from "~app/components/Table/toolbar/BatchButtonExpand";
import BatchDisable from "~app/entities/ImportFileRow/toolbar/BatchDisable";
import BatchEnable from "~app/entities/ImportFileRow/toolbar/BatchEnable";
import BatchReinstallConfig1 from "~app/entities/ImportFileRow/toolbar/BatchReinstallConfig1";
import BatchReinstallConfig2 from "~app/entities/ImportFileRow/toolbar/BatchReinstallConfig2";
import BatchReinstallConfig3 from "~app/entities/ImportFileRow/toolbar/BatchReinstallConfig3";
import BatchAccessTagsAdd from "~app/entities/ImportFileRow/toolbar/BatchAccessTagsAdd";
import BatchAccessTagsDelete from "~app/entities/ImportFileRow/toolbar/BatchAccessTagsDelete";
import BatchVariableAdd from "~app/entities/ImportFileRow/toolbar/BatchVariableAdd";
import BatchVariableDelete from "~app/entities/ImportFileRow/toolbar/BatchVariableDelete";
import BatchTemplateChange from "~app/entities/ImportFileRow/toolbar/BatchTemplateChange";
import BatchLabelsAdd from "~app/entities/ImportFileRow/toolbar/BatchLabelsAdd";
import BatchLabelsDelete from "~app/entities/ImportFileRow/toolbar/BatchLabelsDelete";

interface TableImportFileRowUploadedProps extends Optional<ListProps, "endpointPrefix" | "columns"> {
    importFile: ImportFileInterface;
}

const TableImportFileRowUploaded = ({ importFile, ...props }: TableImportFileRowUploadedProps) => {
    const columns = getColumns();

    return (
        <List
            {...{
                endpointPrefix: "/importfilerow",
                columns,
                enableBatchSelect: true,
                toolbar: (
                    <BatchButtonExpand>
                        <BatchDisable />
                        <BatchEnable />
                        <BatchReinstallConfig1 />
                        <BatchReinstallConfig2 />
                        <BatchReinstallConfig3 />
                        <BatchVariableAdd />
                        <BatchVariableDelete />
                        <BatchTemplateChange />
                        <BatchAccessTagsAdd />
                        <BatchAccessTagsDelete />
                        <BatchLabelsAdd />
                        <BatchLabelsDelete />
                    </BatchButtonExpand>
                ),
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

export default TableImportFileRowUploaded;
export { TableImportFileRowUploadedProps };
