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

import * as webpack from "webpack";
import { mergeWithRules } from "webpack-merge";
import dev from "./webpack.dev";
import { getLocalTsconfigAliases, getLocalWebpackResolveAliases, getLocalWebpackResolveModules } from "./aliases";

// To use webpack with local packages (tested with @arteneo/forge packages) please create aliases.local.ts file that defines local aliases
// You can find example contents of that file in aliases.local.ts.dist
// File aliases.local.ts should NOT be commited to gitaliases.local.ts

// Do not know why mergeWithRules assumes different order of arguments (it is inversed compared to merge function)
const config: webpack.Configuration = mergeWithRules({
    module: {
        rules: {
            test: "match",
            use: {
                loader: "match",
                options: "replace",
            },
        },
    },
})(
    {
        module: {
            rules: [
                {
                    test: /\.tsx?$/,
                    use: {
                        loader: "ts-loader",
                        options: {
                            compilerOptions: {
                                paths: getLocalTsconfigAliases(),
                            },
                        },
                    },
                    exclude: /node_modules/,
                },
            ],
        },
        resolve: {
            alias: getLocalWebpackResolveAliases(),
            modules: getLocalWebpackResolveModules(),
        },
    },
    dev
);

export default config;
