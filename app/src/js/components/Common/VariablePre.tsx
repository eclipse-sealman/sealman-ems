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

import { Box } from "@mui/material";
import React from "react";
import CopyToClipBoard from "react-copy-to-clipboard";
import {
    ContentCopyOutlined,
    HelpOutline,
    KeyboardDoubleArrowDownOutlined,
    KeyboardDoubleArrowUpOutlined,
} from "@mui/icons-material";
import Pre, { PreProps } from "~app/components/Common/Pre";
import { IconButton, TranslateVariablesInterface } from "@arteneo/forge";

interface VariablePreProps extends PreProps {
    length?: number;
    disableCopyToClipBoard?: boolean;
    helpTooltip?: string;
    helpTooltipVariables?: TranslateVariablesInterface;
}

const VariablePre = ({
    content,
    disableCopyToClipBoard = false,
    helpTooltip,
    helpTooltipVariables,
    ...props
}: VariablePreProps) => {
    const preRef = React.useRef<HTMLPreElement | null>(null);

    const [collapsed, setCollapsed] = React.useState(true);
    const [overflowActive, setOverflowActive] = React.useState(false);

    const isOverflowActive = (ref: HTMLPreElement | null): boolean => {
        if (!ref) {
            return false;
        }

        return ref.offsetHeight < ref.scrollHeight || ref.offsetWidth < ref.scrollWidth;
    };

    React.useEffect(() => {
        setOverflowActive(isOverflowActive(preRef?.current));
    }, []);

    let resolvedContent = content;
    if (Array.isArray(resolvedContent)) {
        resolvedContent = "[ " + resolvedContent.join(", ") + " ]";
    }
    if (resolvedContent && typeof resolvedContent == "object") {
        resolvedContent = "[ " + Object.values(resolvedContent).join(", ") + " ]";
    }

    const isCollapsable = resolvedContent && overflowActive;

    return (
        <Box {...{ sx: { display: "flex", alignItems: "flex-start", width: "100%" } }}>
            <Pre
                {...{
                    preRef,
                    content: resolvedContent,
                    sx: {
                        p: 0,
                        border: 0,
                        borderRadius: 0,
                        fontSize: 13,
                        width: "100%",
                        whiteSpace: collapsed ? "nowrap" : "pre-wrap",
                        textOverflow: collapsed ? "ellipsis" : undefined,
                        overflow: "hidden",
                        ...(props?.sx ?? {}),
                    },
                    ...props,
                }}
            />
            {isCollapsable && (
                <IconButton
                    {...{
                        size: "small",
                        onClick: () => setCollapsed(!collapsed),
                        icon: collapsed ? (
                            <KeyboardDoubleArrowDownOutlined {...{ fontSize: "small", sx: { fontSize: 14 } }} />
                        ) : (
                            <KeyboardDoubleArrowUpOutlined {...{ fontSize: "small", sx: { fontSize: 14 } }} />
                        ),
                        tooltip: collapsed ? "variablePre.expand" : "variablePre.collapse",
                        sx: {
                            p: 0.5,
                            mt: -0.5,
                        },
                    }}
                />
            )}
            {!disableCopyToClipBoard && content && (
                <CopyToClipBoard {...{ text: content }}>
                    <IconButton
                        {...{
                            size: "small",
                            icon: <ContentCopyOutlined {...{ fontSize: "small", sx: { fontSize: 14 } }} />,
                            tooltip: "variablePre.copyToClipBoard",
                            sx: {
                                p: 0.5,
                                mt: -0.5,
                            },
                        }}
                    />
                </CopyToClipBoard>
            )}
            {helpTooltip && (
                <IconButton
                    {...{
                        size: "small",
                        icon: <HelpOutline {...{ fontSize: "small", sx: { fontSize: 14 } }} />,
                        tooltip: helpTooltip,
                        tooltipVariables: helpTooltipVariables,
                        sx: {
                            p: 0.5,
                            mt: -0.5,
                        },
                    }}
                />
            )}
        </Box>
    );
};

export default VariablePre;
export { VariablePreProps };
