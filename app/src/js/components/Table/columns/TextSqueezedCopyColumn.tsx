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
import { ColumnPathInterface, IconButton } from "@arteneo/forge";
import { getIn } from "formik";
import { Box } from "@mui/material";
import CopyToClipBoard from "react-copy-to-clipboard";
import { ContentCopyOutlined } from "@mui/icons-material";

interface TextSqueezedCopyColumnProps extends ColumnPathInterface {
    maxWidth?: number;
}

const TextSqueezedCopyColumn = ({ result, columnName, path, maxWidth = 75 }: TextSqueezedCopyColumnProps) => {
    if (typeof columnName === "undefined") {
        throw new Error("TextSqueezedCopyColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("TextSqueezedCopyColumn component: Missing required result prop");
    }

    const value = getIn(result, path ? path : columnName);

    if (!value) {
        return null;
    }

    return (
        <Box {...{ sx: { display: "flex", alignItems: "center" } }}>
            <Box
                {...{
                    sx: { whiteSpace: "nowrap", fontSize: 13, overflow: "hidden", textOverflow: "ellipsis", maxWidth },
                }}
            >
                {value}
            </Box>
            <CopyToClipBoard {...{ text: value }}>
                <IconButton
                    {...{
                        size: "small",
                        icon: <ContentCopyOutlined {...{ fontSize: "small", sx: { fontSize: 15 } }} />,
                        tooltip: "action.copyToClipBoard",
                    }}
                />
            </CopyToClipBoard>
        </Box>
    );
};

export default TextSqueezedCopyColumn;
export { TextSqueezedCopyColumnProps };
