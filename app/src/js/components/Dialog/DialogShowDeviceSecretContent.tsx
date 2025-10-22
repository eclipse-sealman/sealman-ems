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
import { useDialog } from "@arteneo/forge";
import DisplaySecretVariables from "~app/components/Display/DisplaySecretVariables";
import Pre from "~app/components/Common/Pre";
import ButtonCopyToClipboard from "~app/components/Common/ButtonCopyToClipboard";
import DisplaySurfaceTitle from "~app/components/Common/DisplaySurfaceTitle";

const DialogShowDeviceSecretContent = () => {
    const { payload } = useDialog();

    return (
        <Box
            {...{
                sx: {
                    display: "grid",
                    alignItems: "flex-start",
                    gap: { xs: 2, lg: 4 },
                    mb: 2,
                },
            }}
        >
            <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 2 } }}>
                <Box {...{ sx: { display: "flex", gap: 2 } }}>
                    <Pre {...{ sx: { flexGrow: 1 }, content: payload?.secretValue }} />
                    <ButtonCopyToClipboard
                        {...{
                            text: payload?.secretValue,
                            snackbarLabel: "deviceSecret.dialog.snackbar.copyToClipboardSuccess",
                        }}
                    />
                </Box>
            </Box>
            {payload?.encodedVariables && (
                <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 2 } }}>
                    <DisplaySurfaceTitle {...{ title: "deviceSecret.dialog.variables" }} />
                    <DisplaySecretVariables {...{ variables: payload?.encodedVariables, collapseRowsAbove: 12 }} />
                </Box>
            )}
        </Box>
    );
};

export default DialogShowDeviceSecretContent;
