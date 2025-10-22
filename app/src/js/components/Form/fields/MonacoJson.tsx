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
import Monaco, { MonacoProps } from "~app/components/Form/fields/Monaco";

/**
 * Remember to include MonacoWebpackPlugin json language in your webpack configuration.
 *
 * Read more here:
 * https://github.com/microsoft/monaco-editor
 *
 * Example:
 * import MonacoWebpackPlugin from "monaco-editor-webpack-plugin";
 * const config: webpack.Configuration = {
 *    plugins: [
 *       new MonacoWebpackPlugin({
 *           languages: ["json"],
 *       }),
 *    ],
 * }
 */
const MonacoJson = (props: MonacoProps) => {
    return <Monaco {...{ monacoEditorProps: { language: "json" }, languageInformation: "JSON", ...props }} />;
};

export default MonacoJson;
export { MonacoProps as MonacoJsonProps };
