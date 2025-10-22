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
import { editor } from "monaco-editor/esm/vs/editor/editor.api";
import MonacoEditor, { MonacoEditorProps } from "react-monaco-editor";

interface DialogDiffValuesContentValuesProps {
    values: string;
}

const DialogDiffValuesContentValues = ({ values }: DialogDiffValuesContentValuesProps) => {
    const monacoEditorProps: MonacoEditorProps = {
        theme: "vs-light",
        editorDidMount: (editor) => {
            // Sadly it does not formatDocument when readOnly is true in the first place
            // Also timeout seems to be needed, otherwise it does not work
            // No other hook is available at time of writing
            setTimeout(() => {
                // Not sure what is the issue here. TS is not happy, need casting to any
                // eslint-disable-next-line
                (editor as any)
                    .getAction("editor.action.formatDocument")
                    .run()
                    .then(() => editor.updateOptions({ readOnly: true }));
            }, 100);
        },
        options: {
            wordWrap: "on",
            readOnly: false,
            automaticLayout: true,
            snippetSuggestions: "none",
            codeLens: false,
            contextmenu: false,
            formatOnPaste: true,
            inlayHints: {
                enabled: "off",
            },
            inlineSuggest: {
                enabled: false,
            },
            lightbulb: {
                enabled: editor.ShowLightbulbIconMode.Off,
            },
            parameterHints: {
                enabled: false,
            },
            quickSuggestions: false,
            minimap: {
                enabled: false,
            },
        },
        value: values,
        language: "json",
    };

    return <MonacoEditor {...monacoEditorProps} />;
};

export default DialogDiffValuesContentValues;
export { DialogDiffValuesContentValuesProps };
