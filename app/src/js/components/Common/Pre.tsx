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
import { Box, BoxProps } from "@mui/material";

interface PreProps extends BoxProps {
    content: undefined | string;
    preRef?: React.RefObject<HTMLPreElement>;
}

const Pre = ({ content, preRef, ...props }: PreProps) => {
    // We will adjust content that start and/or end with spaces and replace them with &nbsp;
    // Strings with start/end spaces have those spaces removed when white-space is 'normal', 'nowrap', or 'pre-line')
    let adjustedContent = content?.toString() ?? "";

    // In case of 1 space string avoid adding first space (let last space be replaced by &nbsp;)
    const isFirstSpace = adjustedContent.length > 1 && adjustedContent?.charAt(0) === " " ? true : false;
    const isLastSpace = adjustedContent?.charAt(adjustedContent?.length - 1) === " " ? true : false;

    if (isFirstSpace) {
        adjustedContent = adjustedContent?.slice(1);
    }

    if (isLastSpace) {
        adjustedContent = adjustedContent?.slice(0, -1);
    }

    return (
        <Box
            {...{
                component: "pre",
                ref: preRef,
                ...props,
                sx: {
                    margin: 0,
                    borderWidth: 1,
                    borderStyle: "solid",
                    borderColor: "grey.300",
                    backgroundColor: "white",
                    borderRadius: 0.5,
                    px: 1.5,
                    py: 1,
                    wordWrap: "break-word",
                    whiteSpace: "pre-wrap",
                    ...(props?.sx ?? {}),
                },
            }}
        >
            {isFirstSpace && <>&nbsp;</>}
            {adjustedContent}
            {isLastSpace && <>&nbsp;</>}
        </Box>
    );
};

export default Pre;
export { PreProps };
