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
import { Optional, TranslateVariablesInterface } from "@arteneo/forge";
import { Route, Routes } from "react-router-dom";
import List, { ListProps } from "~app/components/Crud/List";
import Details, { DetailsProps } from "~app/components/Crud/Details";
import Edit, { EditProps } from "~app/components/Crud/Edit";
import ChangePassword, { ChangePasswordProps } from "~app/components/Crud/ChangePassword";
import Create, { CreateProps } from "~app/components/Crud/Create";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";
import { DetailsProvider } from "~app/contexts/Details";
import TriggerError404 from "~app/components/TriggerError404";

interface BuilderProps {
    endpointPrefix: string;
    title: string;
    titleVariables?: TranslateVariablesInterface;
    icon: React.ReactNode;
    listSurfaceTitleProps?: SurfaceTitleProps;
    listProps?: Optional<ListProps, "endpointPrefix" | "title" | "titleVariables">;
    createProps?: Optional<CreateProps, "endpointPrefix" | "titleProps">;
    detailsProps?: Optional<DetailsProps, "endpointPrefix" | "titleProps">;
    editProps?: Optional<EditProps, "endpointPrefix" | "titleProps">;
    changePasswordProps?: Optional<ChangePasswordProps, "endpointPrefix" | "titleProps">;
}

const Builder = ({
    endpointPrefix,
    title,
    titleVariables = {},
    icon,
    listProps,
    listSurfaceTitleProps,
    createProps,
    detailsProps,
    editProps,
    changePasswordProps,
}: BuilderProps) => {
    const listTo: undefined | string = typeof listProps !== "undefined" ? "../list" : undefined;

    return (
        <Routes>
            {typeof listProps !== "undefined" && (
                <Route
                    {...{
                        path: "/list",
                        element: (
                            <>
                                <SurfaceTitle
                                    {...{
                                        title,
                                        titleVariables,
                                        titleTo: listTo,
                                        subtitle: "route.subtitle.list",
                                        icon,
                                        ...listSurfaceTitleProps,
                                    }}
                                />
                                <List
                                    {...{
                                        endpointPrefix,
                                        title,
                                        titleVariables,
                                        hasCreate: typeof createProps !== "undefined",
                                        hasDetails: typeof detailsProps !== "undefined",
                                        hasEdit: typeof editProps !== "undefined",
                                        hasDelete: true,
                                        ...listProps,
                                    }}
                                />
                            </>
                        ),
                    }}
                />
            )}
            {typeof createProps !== "undefined" && (
                <Route
                    {...{
                        path: "/create",
                        element: (
                            <Create
                                {...{
                                    endpointPrefix,
                                    ...createProps,
                                    titleProps: {
                                        title,
                                        titleVariables,
                                        icon,
                                        titleTo: listTo,
                                        ...createProps?.titleProps,
                                    },
                                }}
                            />
                        ),
                    }}
                />
            )}
            {typeof editProps !== "undefined" && (
                <Route
                    {...{
                        path: "/edit/:id",
                        element: (
                            <Edit
                                {...{
                                    endpointPrefix,
                                    ...editProps,
                                    titleProps: {
                                        title,
                                        titleVariables,
                                        icon,
                                        titleTo: listTo,
                                        ...editProps?.titleProps,
                                    },
                                }}
                            />
                        ),
                    }}
                />
            )}
            {typeof changePasswordProps !== "undefined" && (
                <Route
                    {...{
                        path: "/changepassword/:id",
                        element: (
                            <ChangePassword
                                {...{
                                    endpointPrefix,
                                    ...changePasswordProps,
                                    titleProps: {
                                        title,
                                        titleVariables,
                                        icon,
                                        titleTo: listTo,
                                        ...changePasswordProps?.titleProps,
                                    },
                                }}
                            />
                        ),
                    }}
                />
            )}
            {typeof detailsProps !== "undefined" && (
                <Route
                    {...{
                        path: "/details/:id",
                        element: (
                            <DetailsProvider>
                                <Details
                                    {...{
                                        endpointPrefix,
                                        ...detailsProps,
                                        titleProps: {
                                            title,
                                            titleVariables,
                                            icon,
                                            titleTo: listTo,
                                            ...detailsProps?.titleProps,
                                        },
                                    }}
                                />
                            </DetailsProvider>
                        ),
                    }}
                />
            )}
            <Route path="*" element={<TriggerError404 />} />
        </Routes>
    );
};

export default Builder;
