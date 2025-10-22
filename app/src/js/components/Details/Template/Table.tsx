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
import { TemplateInterface } from "~app/entities/Template/definitions";
import List, { ListProps } from "~app/components/Crud/List";

interface TableProps extends Optional<ListProps, "endpointPrefix"> {
    template: TemplateInterface;
}

const Table = ({ template, ...props }: TableProps) => {
    return (
        <List
            {...{
                endpointPrefix: "/templateversion",
                hasDetails: true,
                hasEdit: true,
                // It has a duplicate, but it has to be a custom duplicate action which reloads whole details screen
                hasDuplicate: false,
                hasDelete: true,
                ...props,
                defaultSorting: {
                    updatedAt: "desc",
                    ...(props.defaultSorting ?? {}),
                },
                editProps: {
                    to: (result) => "/templateversion/edit/" + result.id,
                    ...(props.editProps ?? {}),
                },
                additionalFilters: {
                    template: {
                        filterBy: "template",
                        filterType: "equal",
                        filterValue: template.id,
                    },
                    ...(props.additionalFilters ?? {}),
                },
            }}
        />
    );
};

export default Table;
export { TableProps };
