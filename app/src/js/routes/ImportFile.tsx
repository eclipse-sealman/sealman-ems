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
import { useNavigate } from "react-router-dom";
import { DownloadOutlined } from "@mui/icons-material";
import { useLoader, useSnackbar } from "@arteneo/forge";
import getColumns from "~app/entities/ImportFile/columns";
import getFilters from "~app/entities/ImportFile/filters";
import getFields from "~app/entities/ImportFile/fields";
import Builder from "~app/components/Crud/Builder";
import ImportFileFieldset from "~app/fieldsets/ImportFileFieldset";
import ImportFileDetails from "~app/components/Details/ImportFile/ImportFileDetails";

const ImportFile = () => {
    const navigate = useNavigate();
    const { hideLoader } = useLoader();
    const { showSuccess } = useSnackbar();

    const columns = getColumns();
    const filters = getFilters();
    const fields = getFields();

    return (
        <Builder
            {...{
                endpointPrefix: "/importfile",
                title: "route.title.importFile",
                icon: <DownloadOutlined />,
                listProps: {
                    // Create button is in sidebar. Skip it in the toolbar
                    hasCreate: false,
                    // Custom details button is used in actions
                    hasDetails: false,
                    columns,
                    filters,
                    defaultSorting: {
                        createdAt: "desc",
                    },
                },
                createProps: {
                    fields,
                    onSubmitSuccess: (defaultOnSubmitSuccess, values, helpers, response) => {
                        showSuccess("importFile.start.snackbar");
                        hideLoader();

                        const importFile = response.data;
                        navigate("/importfile/details/" + importFile.id);
                    },
                    children: <ImportFileFieldset {...{ fields }} />,
                },
                detailsProps: {
                    render: (object) => <ImportFileDetails {...{ importFile: object }} />,
                },
            }}
        />
    );
};

export default ImportFile;
