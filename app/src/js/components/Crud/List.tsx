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
import { Optional, ResultInterface, TranslateVariablesInterface } from "@arteneo/forge";
import BuilderActionsColumn, { BuilderActionsColumnProps } from "~app/components/Table/columns/BuilderActionsColumn";
import BuilderToolbar, { BuilderToolbarProps } from "~app/components/Table/toolbar/BuilderToolbar";
import Table, { TableProps } from "~app/components/Table/components/Table";
import ResultDetails, { ResultDetailsProps } from "~app/components/Table/actions/ResultDetails";
import ResultEdit, { ResultEditProps } from "~app/components/Table/actions/ResultEdit";
import ResultDelete, { ResultDeleteDialogProps, ResultDeleteProps } from "~app/components/Table/actions/ResultDelete";
import ResultDuplicate, {
    ResultDuplicateDialogProps,
    ResultDuplicateProps,
} from "~app/components/Table/actions/ResultDuplicate";
import Create, { CreateProps } from "~app/components/Table/toolbar/Create";
import ExportCsv, { ExportCsvProps } from "~app/components/Table/toolbar/ExportCsv";
import ExportExcel, { ExportExcelProps } from "~app/components/Table/toolbar/ExportExcel";

interface ListDuplicateDialogPropsReturnType extends Omit<ResultDuplicateDialogProps, "confirmProps"> {
    confirmProps?: Optional<ResultDuplicateDialogProps["confirmProps"], "endpoint">;
}

interface ListDuplicateProps extends Omit<ResultDuplicateProps, "dialogProps"> {
    dialogProps?: (result: ResultInterface) => ListDuplicateDialogPropsReturnType;
}

interface ListDeleteDialogPropsReturnType extends Omit<ResultDeleteDialogProps, "confirmProps"> {
    confirmProps?: Optional<ResultDeleteDialogProps["confirmProps"], "endpoint">;
}

interface ListDeleteProps extends Omit<ResultDeleteProps, "dialogProps"> {
    dialogProps?: (result: ResultInterface) => ListDeleteDialogPropsReturnType;
}

interface ListProps extends Optional<TableProps, "endpoint"> {
    endpointPrefix: string;
    title?: string;
    titleVariables?: TranslateVariablesInterface;
    hasDetails?: boolean;
    detailsProps?: ResultDetailsProps;
    hasEdit?: boolean;
    editProps?: ResultEditProps;
    hasDuplicate?: boolean;
    duplicateProps?: ListDuplicateProps;
    hasDelete?: boolean;
    deleteProps?: ListDeleteProps;
    hasCreate?: boolean;
    createProps?: CreateProps;
    hasExportCsv?: boolean;
    exportCsvProps?: ExportCsvProps;
    hasExportExcel?: boolean;
    exportExcelProps?: ExportExcelProps;
}

const List = ({
    endpointPrefix,
    title,
    titleVariables = {},
    hasDetails,
    detailsProps,
    hasEdit,
    editProps,
    hasDuplicate,
    duplicateProps,
    hasDelete,
    deleteProps,
    hasCreate,
    createProps,
    hasExportCsv,
    exportCsvProps,
    hasExportExcel,
    exportExcelProps,
    columns,
    // TODO Arek Fix spacing / showing toolbar when it is empty i.e in logs. Arek: I checked this on 12.07. I do not have a good idea how to solve it.
    toolbar = <BuilderToolbar />,
    ...tableProps
}: ListProps) => {
    if (typeof columns.actions !== "undefined" && (hasDetails || hasEdit || hasDuplicate || hasDelete)) {
        if (React.isValidElement(columns.actions) && columns.actions.type === BuilderActionsColumn) {
            columns.actions = React.cloneElement(columns.actions as React.ReactElement<BuilderActionsColumnProps>, {
                detailsAction: hasDetails ? <ResultDetails {...detailsProps} /> : undefined,
                editAction: hasEdit ? <ResultEdit {...editProps} /> : undefined,
                duplicateAction: hasDuplicate ? (
                    <ResultDuplicate
                        {...{
                            ...duplicateProps,
                            dialogProps: (result) => {
                                if (!duplicateProps?.dialogProps) {
                                    // Return default dialogProps
                                    return {
                                        confirmProps: {
                                            endpoint: endpointPrefix + "/" + result?.id + "/duplicate",
                                        },
                                    };
                                }

                                // Resolve passed dialogProps and merge them with default ones
                                const resolvedDuplicateDialogProps = duplicateProps.dialogProps(result);

                                return {
                                    ...resolvedDuplicateDialogProps,
                                    confirmProps: {
                                        endpoint: endpointPrefix + "/" + result?.id + "/duplicate",
                                        ...resolvedDuplicateDialogProps?.confirmProps,
                                    },
                                };
                            },
                        }}
                    />
                ) : undefined,
                deleteAction: hasDelete ? (
                    <ResultDelete
                        {...{
                            ...deleteProps,
                            dialogProps: (result) => {
                                if (!deleteProps?.dialogProps) {
                                    // Return default dialogProps
                                    return {
                                        confirmProps: {
                                            endpoint: endpointPrefix + "/" + result?.id,
                                        },
                                    };
                                }

                                // Resolve passed dialogProps and merge them with default ones
                                const resolvedDeleteDialogProps = deleteProps.dialogProps(result);

                                return {
                                    ...resolvedDeleteDialogProps,
                                    confirmProps: {
                                        endpoint: endpointPrefix + "/" + result?.id,
                                        ...resolvedDeleteDialogProps?.confirmProps,
                                    },
                                };
                            },
                        }}
                    />
                ) : undefined,
            });
        }
    }

    if (typeof toolbar !== "undefined" && (hasCreate || hasExportCsv || hasExportExcel)) {
        if (React.isValidElement(toolbar) && toolbar.type === BuilderToolbar) {
            toolbar = React.cloneElement(toolbar as React.ReactElement<BuilderToolbarProps>, {
                createAction: hasCreate ? <Create {...createProps} /> : undefined,
                exportCsvAction: hasExportCsv ? (
                    <ExportCsv
                        {...{
                            endpoint: endpointPrefix + "/export/csv",
                            labelTitle: title,
                            labelTitleVariables: titleVariables,
                            ...exportCsvProps,
                        }}
                    />
                ) : undefined,
                exportExcelAction: hasExportExcel ? (
                    <ExportExcel
                        {...{
                            endpoint: endpointPrefix + "/export/excel",
                            labelTitle: title,
                            labelTitleVariables: titleVariables,
                            ...exportExcelProps,
                        }}
                    />
                ) : undefined,
            });
        }
    }

    return (
        <Table
            {...{
                endpoint: endpointPrefix + "/list",
                columns,
                toolbar,
                visibleColumnsEndpoint: "/usertable/columns",
                ...tableProps,
            }}
        />
    );
};

export default List;
export {
    ListDuplicateDialogPropsReturnType,
    ListDuplicateProps,
    ListDeleteDialogPropsReturnType,
    ListDeleteProps,
    ListProps,
};
