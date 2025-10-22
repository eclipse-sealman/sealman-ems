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

import _ from "lodash";
import React from "react";
import { editor } from "monaco-editor/esm/vs/editor/editor.api";
import { MonacoDiffEditor, MonacoDiffEditorProps } from "react-monaco-editor";

interface DialogDiffValuesContentDiffProps {
    oldValues: string;
    newValues: string;
    onlyChanges?: boolean;
}

const DialogDiffValuesContentDiff = ({
    newValues,
    oldValues,
    onlyChanges = false,
}: DialogDiffValuesContentDiffProps) => {
    const monacoEditorProps: MonacoDiffEditorProps = {
        theme: "vs-light",
        editorDidMount: (editor) => {
            const originalEditor = editor.getOriginalEditor();
            // Sadly it does not formatDocument when readOnly is true in the first place
            // Also timeout seems to be needed, otherwise it does not work
            // No other hook is available at time of writing
            setTimeout(() => {
                // Not sure what is the issue here. TS is not happy, need casting to any
                // eslint-disable-next-line
                (originalEditor as any)
                    .getAction("editor.action.formatDocument")
                    .run()
                    .then(() => originalEditor.updateOptions({ readOnly: true }));
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
        language: "json",
    };

    const getFormattedJson = (object: object): string => {
        if (Object.keys(object).length === 0) {
            return "";
        }

        // JSON.stringify additionally formats JSON as MonacoDiffEditor is not formatting it correctly
        return JSON.stringify(object, null, 2);
    };

    const oldObject = oldValues ? JSON.parse(oldValues) : {};
    const newObject = newValues ? JSON.parse(newValues) : {};

    if (onlyChanges) {
        // Get all keys from both objects
        const keys = [...new Set(Object.keys(oldObject).concat(Object.keys(newObject)))];
        const keepKeys = keys.filter((key) => {
            // null is also typeof "object"
            if (oldObject?.[key] === null && newObject?.[key] === null) {
                return false;
            }

            // typeof "object" also covers arrays
            if (typeof oldObject?.[key] === "object" && typeof newObject?.[key] === "object") {
                return JSON.stringify(oldObject?.[key]) !== JSON.stringify(newObject?.[key]);
            }

            return oldObject?.[key] !== newObject?.[key];
        });

        monacoEditorProps.original = getFormattedJson(_.pick(oldObject, ...keepKeys));
        monacoEditorProps.value = getFormattedJson(_.pick(newObject, ...keepKeys));
    } else {
        monacoEditorProps.original = getFormattedJson(oldObject);
        monacoEditorProps.value = getFormattedJson(newObject);
    }

    return <MonacoDiffEditor {...monacoEditorProps} />;
};

export default DialogDiffValuesContentDiff;
export { DialogDiffValuesContentDiffProps };
