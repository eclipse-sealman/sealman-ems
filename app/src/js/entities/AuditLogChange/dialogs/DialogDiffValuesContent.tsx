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
import { Alert, Box, FormControlLabel, Radio, RadioGroup } from "@mui/material";
import { useTranslation } from "react-i18next";
import { Resizable } from "re-resizable";
import DialogDiffValuesContentValues from "~app/entities/AuditLogChange/dialogs/DialogDiffValuesContentValues";
import DialogDiffValuesContentDiff from "~app/entities/AuditLogChange/dialogs/DialogDiffValuesContentDiff";

interface DialogDiffValuesContentProps {
    oldValues: string;
    newValues: string;
    onlyChanges: boolean;
}

type DialogDiffValuesContentType = "diff" | "changes" | "new" | "old";

const CONTENT_TYPE_STORAGE_KEY = "dialog_diff_content_type";

const DialogDiffValuesContent = ({ newValues, oldValues, onlyChanges }: DialogDiffValuesContentProps) => {
    const { t } = useTranslation();

    let initialContentType = localStorage.getItem(CONTENT_TYPE_STORAGE_KEY) as DialogDiffValuesContentType;
    if (initialContentType === "diff" && onlyChanges) {
        initialContentType = "changes";
    }

    const [contentType, setContentType] = React.useState<DialogDiffValuesContentType>(initialContentType ?? "diff");

    const options: DialogDiffValuesContentType[] = ["changes", "old", "new"];
    if (!onlyChanges) {
        options.unshift("diff");
    }

    const onChange = (event: React.ChangeEvent<HTMLInputElement>, value: string) => {
        setContentType(value as DialogDiffValuesContentType);
        localStorage.setItem(CONTENT_TYPE_STORAGE_KEY, value);
    };

    return (
        <>
            {onlyChanges && <Alert severity="info">{t("auditLog.dialog.diffValues.alert.onlyChanges")}</Alert>}
            <RadioGroup value={contentType} row onChange={onChange} sx={{ mb: 1 }}>
                {options.map((option, key) => (
                    <FormControlLabel
                        key={key}
                        {...{
                            value: option,
                            control: <Radio />,
                            label: t("auditLog.dialog.diffValues.contentType." + option) as string,
                        }}
                    />
                ))}
            </RadioGroup>

            <Box
                {...{
                    // contentEditable: true enables copy/paste from default context menu on right click (might not work in all browsers)
                    // suppressContentEditableWarning: true prevents warning from React that user can change content directly in browser as we are handling it with MonacoEditor
                    contentEditable: true,
                    suppressContentEditableWarning: true,
                    sx: {
                        overflow: "hidden",
                        borderRadius: 1,
                        borderWidth: 1,
                        borderStyle: "solid",
                        borderColor: "grey.400",
                    },
                }}
            >
                <Resizable
                    {...{
                        defaultSize: {
                            width: "100%",
                            height: 300,
                        },
                        enable: {
                            top: false,
                            right: false,
                            bottom: true,
                            left: false,
                            topRight: false,
                            bottomRight: false,
                            bottomLeft: false,
                            topLeft: false,
                        },
                    }}
                >
                    {contentType === "diff" && (
                        <DialogDiffValuesContentDiff oldValues={oldValues} newValues={newValues} />
                    )}
                    {contentType === "changes" && (
                        <DialogDiffValuesContentDiff oldValues={oldValues} newValues={newValues} onlyChanges />
                    )}
                    {contentType === "old" && <DialogDiffValuesContentValues values={oldValues} />}
                    {contentType === "new" && <DialogDiffValuesContentValues values={newValues} />}
                </Resizable>
            </Box>
        </>
    );
};

export default DialogDiffValuesContent;
export { DialogDiffValuesContentProps };
