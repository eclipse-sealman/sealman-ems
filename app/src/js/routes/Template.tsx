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
import { ContentCopyOutlined } from "@mui/icons-material";
import getColumns from "~app/entities/Template/columns";
import getFilters from "~app/entities/Template/filters";
import Builder from "~app/components/Crud/Builder";
import TemplateDetails from "~app/components/Details/Template/TemplateDetails";
import { useUser } from "~app/contexts/User";

const Template = () => {
    const { isAccessGranted } = useUser();
    const columns = getColumns();
    const filters = getFilters(undefined, !isAccessGranted({ admin: true }) ? ["updatedBy", "createdBy"] : undefined);

    return (
        <Builder
            {...{
                endpointPrefix: "/template",
                title: "route.title.template",
                icon: <ContentCopyOutlined />,
                listProps: {
                    columns,
                    filters,
                    hasCreate: true,
                    hasEdit: true,
                    editProps: {
                        label: "action.rename",
                    },
                    defaultSorting: {
                        createdAt: "desc",
                    },
                },
                detailsProps: {
                    objectTitleProps: (object) => ({
                        subtitle: "route.subtitle.detailsRepresentationDeviceType",
                        subtitleVariables: {
                            representation: object.representation,
                            deviceType: object.deviceType?.name,
                        },
                        disableSubtitleTranslate: false,
                    }),
                    render: (object) => <TemplateDetails {...{ template: object }} />,
                },
            }}
        />
    );
};

export default Template;
