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
import { Button } from "@arteneo/forge";
import { KeyboardDoubleArrowDownOutlined, KeyboardDoubleArrowUpOutlined } from "@mui/icons-material";
import DisplaySecretVariableRow from "~app/components/Display/DisplaySecretVariableRow";
import { DisplaySecretVariableTitleProps } from "~app/components/Display/DisplaySecretVariableTitle";
import DisplayWrapper from "~app/components/Display/DisplayWrapper";

interface DisplaySecretVariablePanelRowsInterface {
    // eslint-disable-next-line
    [key: string]: React.ReactElement<any>;
}

interface DisplaySecretVariablePanelProps {
    // eslint-disable-next-line
    result: any;
    rows: DisplaySecretVariablePanelRowsInterface;
    getTitleProps?: (rowKey: string) => DisplaySecretVariableTitleProps;
    collapseRowsAbove?: null | number;
}

const DisplaySecretVariablePanel = ({
    result,
    rows,
    getTitleProps = (rowKey: string): DisplaySecretVariableTitleProps => ({
        title: "label." + rowKey,
    }),
    collapseRowsAbove = 16,
}: DisplaySecretVariablePanelProps) => {
    const [collapsed, setCollapsed] = React.useState(true);

    const rowKeys = Object.keys(rows);
    const isCollapsable = collapseRowsAbove !== null && rowKeys.length > collapseRowsAbove;
    const visibleRowKeys = isCollapsable && collapsed ? rowKeys.slice(0, collapseRowsAbove as number) : rowKeys;

    return (
        <DisplayWrapper>
            {visibleRowKeys.map((rowKey) => (
                <DisplaySecretVariableRow key={rowKey} {...getTitleProps(rowKey)}>
                    {React.cloneElement(rows[rowKey], {
                        result,
                        columnName: rows[rowKey].props?.rowKey ?? rowKey,
                    })}
                </DisplaySecretVariableRow>
            ))}
            {isCollapsable && (
                <Button
                    {...{
                        label: collapsed ? "display.expand" : "display.collapse",
                        color: "primary",
                        size: "small",
                        endIcon: collapsed ? <KeyboardDoubleArrowDownOutlined /> : <KeyboardDoubleArrowUpOutlined />,
                        onClick: () => setCollapsed(!collapsed),
                        sx: {
                            fontSize: 14,
                            borderTopWidth: "1px",
                            borderTopStyle: "dashed",
                            borderTopColor: "grey.300",
                            borderRadius: 0,
                        },
                    }}
                />
            )}
        </DisplayWrapper>
    );
};

export default DisplaySecretVariablePanel;
export { DisplaySecretVariablePanelProps, DisplaySecretVariablePanelRowsInterface };
