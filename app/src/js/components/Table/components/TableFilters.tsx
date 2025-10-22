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
import { Accordion, AccordionDetails, AccordionSummary, Box, Chip, Typography } from "@mui/material";
import { ExpandMore, FilterList } from "@mui/icons-material";
import { Form, useTable } from "@arteneo/forge";
import TableFiltersFieldset from "~app/components/Table/components/TableFiltersFieldset";

const TableFilters = () => {
    const { t } = useTranslation();
    const { filters, filterFields, onSubmitFilters } = useTable();

    const [filtersExpanded, setFiltersExpanded] = React.useState(false);

    if (typeof filterFields === "undefined") {
        return null;
    }

    const activeFilters = Object.keys(filters).filter((filterKey) => {
        const filter = filters[filterKey];
        if (Array.isArray(filter) && filter.length <= 0) {
            return false;
        }

        // Boolean filters use false value
        if (filter === false) {
            return true;
        }

        return filter ? true : false;
    });
    const activeFilterCount = activeFilters.length;

    return (
        <Box mb={2}>
            <Accordion
                {...{
                    expanded: filtersExpanded,
                    onChange: () => setFiltersExpanded(!filtersExpanded),
                    TransitionProps: {
                        unmountOnExit: true,
                    },
                }}
            >
                <AccordionSummary expandIcon={<ExpandMore />}>
                    <Box {...{ display: "flex", mr: 1 }}>
                        <FilterList />
                    </Box>
                    <Typography
                        {...{ variant: "body1", component: "h2", sx: { display: "flex", alignItems: "center" } }}
                    >
                        {t("table.filters." + (filtersExpanded ? "collapse" : "expand"))}
                        {activeFilterCount > 0 && (
                            <Chip
                                {...{
                                    label: activeFilterCount,
                                    color: "primary",
                                    size: "small",
                                    sx: {
                                        marginLeft: 2,
                                        fontWeight: 600,
                                        height: 20,
                                        fontSize: 14,
                                    },
                                }}
                            />
                        )}
                    </Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Box p={1} pt={0}>
                        <Form
                            {...{
                                fields: filterFields,
                                initialValues: filters,
                                onSubmit: onSubmitFilters,
                                children: <TableFiltersFieldset {...{ fields: filterFields }} />,
                            }}
                        />
                    </Box>
                </AccordionDetails>
            </Accordion>
        </Box>
    );
};

export default TableFilters;
