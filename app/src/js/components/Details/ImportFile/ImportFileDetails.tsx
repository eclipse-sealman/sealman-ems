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
import { ImportFileInterface } from "~app/entities/ImportFile/definitions";
import TableImportFileRowUploaded from "~app/components/Details/ImportFile/TableImportFileRowUploaded";
import TableImportFileRowFinished from "~app/components/Details/ImportFile/TableImportFileRowFinished";
import ImportFileStart from "~app/components/Details/ImportFile/ImportFileStart";
import { ImportFileProvider } from "~app/contexts/ImportFile";

interface ImportFileDetailsProps {
    importFile: ImportFileInterface;
}

const ImportFileDetails = ({ importFile }: ImportFileDetailsProps) => {
    return (
        <>
            {importFile.status === "finished" ? (
                <TableImportFileRowFinished {...{ importFile }} />
            ) : (
                <ImportFileProvider>
                    <Box {...{ sx: { position: "fixed", right: "23px", top: "76px", zIndex: 10 } }}>
                        <ImportFileStart {...{ importFile }} />
                    </Box>
                    <TableImportFileRowUploaded {...{ importFile }} />
                </ImportFileProvider>
            )}
        </>
    );
};

export default ImportFileDetails;
export { ImportFileDetailsProps };
