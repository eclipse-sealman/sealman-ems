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

import * as path from "path";
import * as webpack from "webpack";
import ESLintPlugin from "eslint-webpack-plugin";
import MonacoWebpackPlugin from "monaco-editor-webpack-plugin";
import { getTsconfigAliases, getWebpackResolveAliases } from "./aliases";

const config: webpack.Configuration = {
    entry: path.join(__dirname, "src/js", "index.tsx"),
    module: {
        rules: [
            {
                test: /\.css$/i,
                use: ["style-loader", "css-loader"],
            },
            {
                test: /\.(png|svg|webp|jpg|jpeg|gif)$/i,
                type: "asset/resource",
            },
            {
                test: /\.(woff|woff2|eot|ttf|otf)$/i,
                type: "asset/resource",
            },
            {
                test: /\.tsx?$/,
                use: {
                    loader: "ts-loader",
                    options: {
                        compilerOptions: {
                            paths: getTsconfigAliases(),
                        },
                    },
                },
                exclude: /node_modules/,
            },
        ],
    },
    resolve: {
        extensions: [".tsx", ".ts", ".js"],
        alias: getWebpackResolveAliases(),
    },
    plugins: [
        new ESLintPlugin({ extensions: ["js", "jsx", "ts", "tsx"] }),
        new MonacoWebpackPlugin({
            // Available options are documented at https://github.com/Microsoft/monaco-editor-webpack-plugin#options
            languages: ["json", "shell"],
        }),
    ],
    output: {
        filename: "main.js",
        path: path.resolve(__dirname, "../public/app"),
    },
};

export default config;
