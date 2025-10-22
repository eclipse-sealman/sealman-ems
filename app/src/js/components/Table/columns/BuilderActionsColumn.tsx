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
import { ColumnInterface } from "@arteneo/forge";

interface BuilderActionsColumnRenderActionsInterface {
    detailsAction?: React.ReactNode;
    editAction?: React.ReactNode;
    duplicateAction?: React.ReactNode;
    deleteAction?: React.ReactNode;
}

type BuilderActionsColumnSpecificProps = BuilderActionsColumnRenderActionsInterface & ColumnInterface;

interface BuilderActionsColumnProps extends BuilderActionsColumnSpecificProps {
    render?: (actions: BuilderActionsColumnRenderActionsInterface) => React.ReactNode;
}

const BuilderActionsColumn = ({
    detailsAction,
    editAction,
    duplicateAction,
    deleteAction,
    render = ({ detailsAction, editAction, duplicateAction, deleteAction }) => [
        detailsAction,
        editAction,
        duplicateAction,
        deleteAction,
    ],
    result,
    columnName,
}: BuilderActionsColumnProps) => {
    if (typeof columnName === "undefined") {
        throw new Error("BuilderActionsColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("BuilderActionsColumn component: Missing required result prop");
    }

    const getChildren = () => {
        const children: React.ReactNode[] = [];

        React.Children.toArray(render({ detailsAction, editAction, duplicateAction, deleteAction })).forEach(
            (child) => {
                if (!React.isValidElement(child)) {
                    return;
                }

                if (child.type === React.Fragment) {
                    child.props.children.forEach((childChild: React.ReactNode) => {
                        if (!React.isValidElement(childChild)) {
                            return;
                        }

                        children.push(
                            // Do not know how to solve TS problem here
                            // eslint-disable-next-line
                            React.cloneElement(childChild as React.ReactElement<any>, {
                                result,
                            })
                        );
                    });
                } else {
                    children.push(
                        // Do not know how to solve TS problem here
                        // eslint-disable-next-line
                        React.cloneElement(child as React.ReactElement<any>, {
                            result,
                        })
                    );
                }
            }
        );

        return children;
    };

    return (
        <Box sx={{ display: "flex", flexWrap: "wrap", gap: 1 }}>
            {getChildren().map((child, key) => (
                <React.Fragment key={key}>{child}</React.Fragment>
            ))}
        </Box>
    );
};

// * It has to be done via .defaultProps so disableSorting is passed openly to this component and can be read by TableContent
BuilderActionsColumn.defaultProps = {
    disableSorting: true,
};

export default BuilderActionsColumn;
export { BuilderActionsColumnProps, BuilderActionsColumnRenderActionsInterface };
