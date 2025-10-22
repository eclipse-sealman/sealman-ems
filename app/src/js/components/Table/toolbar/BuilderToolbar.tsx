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
import { Box } from "@mui/material";
import { resolveEndpoint, useTable, VisibleColumns } from "@arteneo/forge";

interface BuilderToolbarRenderActionsInterface {
    createAction?: React.ReactNode;
    exportCsvAction?: React.ReactNode;
    exportExcelAction?: React.ReactNode;
}

interface BuilderToolbarProps {
    createAction?: React.ReactNode;
    exportCsvAction?: React.ReactNode;
    exportExcelAction?: React.ReactNode;
    render?: (actions: BuilderToolbarRenderActionsInterface) => React.ReactNode;
}

const BuilderToolbar = ({
    createAction,
    exportCsvAction,
    exportExcelAction,
    render = ({ createAction, exportCsvAction, exportExcelAction }) => (
        <>
            {createAction}
            {exportCsvAction}
            {exportExcelAction}
        </>
    ),
}: BuilderToolbarProps) => {
    const { visibleColumnsKey, visibleColumnsEndpoint } = useTable();

    let hasVisibleColumns = false;

    if (typeof visibleColumnsKey !== "undefined") {
        const visibleColumnsRequestConfig = resolveEndpoint(visibleColumnsEndpoint);

        if (typeof visibleColumnsRequestConfig !== "undefined") {
            hasVisibleColumns = true;
        }
    }

    return (
        <Box sx={{ display: "flex", gap: 1, flexGrow: 1 }}>
            <Box sx={{ display: "flex", flexWrap: "wrap", gap: 1 }}>
                {render({ createAction, exportCsvAction, exportExcelAction })}
            </Box>
            {hasVisibleColumns && (
                <Box {...{ sx: { display: "flex", alignSelf: "center", marginLeft: "auto" } }}>
                    {/* Outlined icon for ViewColumn looks wierd, keep the default one */}
                    <VisibleColumns
                        {...{
                            size: "small",
                            dialogProps: {
                                arrangeProps: { resetVisibleColumnsProps: { endpoint: "/usertable/columns/edit" } },
                                confirmProps: { endpoint: "/usertable/columns/edit" },
                            },
                        }}
                    />
                </Box>
            )}
        </Box>
    );
};

export default BuilderToolbar;
export { BuilderToolbarProps };
