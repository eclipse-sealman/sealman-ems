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
import { Box } from "@mui/material";
import { useDialog, resolveDialogPayload, ResolveDialogPayloadType } from "@arteneo/forge";
import { Resizable } from "re-resizable";
import MonacoEditor, { MonacoEditorProps } from "react-monaco-editor";

type MonacoLanguageType = "plain" | "json";

interface DialogMonacoContentProps {
    content: ResolveDialogPayloadType<string>;
    language?: ResolveDialogPayloadType<MonacoLanguageType>;
    disableFormatOnMount?: boolean;
    monacoEditorProps?: ResolveDialogPayloadType<MonacoEditorProps>;
}

const DialogMonacoContent = ({
    content,
    language = "plain",
    disableFormatOnMount = false,
    monacoEditorProps = {},
}: DialogMonacoContentProps) => {
    const { payload, initialized } = useDialog();

    const resolvedContent = resolveDialogPayload<string>(content, payload, initialized);
    const resolvedLanguage = resolveDialogPayload<MonacoLanguageType>(language, payload, initialized);
    const resolvedMonacoEditorProps = resolveDialogPayload<MonacoEditorProps>(monacoEditorProps, payload, initialized);

    const internalMonacoEditorProps: MonacoEditorProps = {
        theme: "vs-light",
        editorDidMount: (editor) => {
            if (disableFormatOnMount) {
                return;
            }

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
        value: resolvedContent,
    };

    if (resolvedLanguage === "json") {
        internalMonacoEditorProps.language = "json";
    }

    const mergedMonacoEditorProps = Object.assign(internalMonacoEditorProps, resolvedMonacoEditorProps);

    return (
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
                <MonacoEditor {...mergedMonacoEditorProps} />
            </Resizable>
        </Box>
    );
};

export default DialogMonacoContent;
export { MonacoLanguageType, DialogMonacoContentProps };
