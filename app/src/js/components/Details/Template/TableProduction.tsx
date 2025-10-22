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
import composeGetColumns from "~app/entities/TemplateVersion/columns";
import Table, { TableProps } from "~app/components/Details/Template/Table";
import { useUser } from "~app/contexts/User";

type TableProductionProps = Optional<TableProps, "columns">;

const TableProduction = ({ template }: TableProductionProps) => {
    const { isAccessGranted } = useUser();

    const getColumns = composeGetColumns(
        template.deviceType,
        !isAccessGranted({ admin: true }),
        !isAccessGranted({ adminVpn: true })
    );
    const columns = getColumns();

    return (
        <Table
            {...{
                template,
                columns,
                additionalFilters: {
                    type: {
                        filterBy: "type",
                        filterType: "equal",
                        filterValue: "production",
                    },
                },
                visibleColumnsKey: "templateVersionProduction",
                defaultColumns: ["name", "description", "createdAt", "actions"],
            }}
        />
    );
};

export default TableProduction;
export { TableProductionProps };
