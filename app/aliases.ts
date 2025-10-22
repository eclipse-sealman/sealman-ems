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

// Helper functions for aliases. Place this file in the same folder as webpack configuration (i.e. webpack.ts)
import fs from "fs";
import * as path from "path";

// Load local aliases with fallback
const localAliasesFile = "./aliases.local.ts";
let localAliases: AliasesInterface = {};
if (fs.existsSync(localAliasesFile)) {
    // eslint-disable-next-line
    localAliases = require(localAliasesFile).default;
}

/**
 * Example value as follows:
 * "~app": "src/js"
 * Note! Do not add * at the end
 * Use getTsconfigAliases to get aliases for tsconfig.json `compilerOptions.paths`
 * Use getWebpackResolveAliases to get aliases webpacks resolve option `resolve.aliases`
 */
interface AliasesInterface {
    [index: string]: string;
}

interface TsconfigAliasesInterface {
    [index: string]: string[];
}

// Aliases used in tsconfig.json and injected into webpack
// Unfortunately to enable VSC to autocomplete and autoimport properly you need to copy those also to tsconfig.json under `compilerOptions.paths`
const aliases: AliasesInterface = {
    "~app": "src/js",
    "~translations": "src/translations",
    "~assets": "src/assets",
    "~images": "src/assets/images",
};

// Aliases for tsconfig.json should have a `/*` suffix keys and values should be an array also with `/*` suffix
const getTsconfigAliases = (): TsconfigAliasesInterface => {
    const tsConfigAliases: TsconfigAliasesInterface = {};

    Object.keys(aliases).forEach((alias) => {
        tsConfigAliases[alias + "/*"] = [aliases[alias] + "/*"];
    });

    return tsConfigAliases;
};

// Aliases for webpacks resolve option should have a `/*` suffix
const getWebpackResolveAliases = (): AliasesInterface => {
    const webpackResolveAliases: AliasesInterface = {};

    Object.keys(aliases).forEach((alias) => {
        webpackResolveAliases[alias] = path.resolve(__dirname, aliases[alias]);
    });

    return webpackResolveAliases;
};

// All local tsconfig aliases should include both versions of alias (with `/*` suffix and without)
const getLocalTsconfigAliases = (): TsconfigAliasesInterface => {
    const localTsConfigAliases = getTsconfigAliases();

    Object.keys(localAliases).forEach((localAlias) => {
        localTsConfigAliases[localAlias] = [localAliases[localAlias]];
        localTsConfigAliases[localAlias + "/*"] = [localAliases[localAlias] + "/*"];
    });

    return localTsConfigAliases;
};

// Aliases for webpacks resolve option should have a `/*` suffix
const getLocalWebpackResolveAliases = (): AliasesInterface => {
    const webpackLocalResolveAliases: AliasesInterface = {};

    Object.keys(localAliases).forEach((localAlias) => {
        webpackLocalResolveAliases[localAlias] = path.resolve(__dirname, localAliases[localAlias]);
    });

    return webpackLocalResolveAliases;
};

// Local aliases for webpacks resolve modules option include all local aliases and node_modules folder
const getLocalWebpackResolveModules = (): string[] => {
    const localWebpackResolveModules: string[] = Object.values(localAliases).map((localAlias) =>
        path.resolve(__dirname, localAlias)
    );
    localWebpackResolveModules.push(path.resolve(__dirname, "node_modules"));

    return localWebpackResolveModules;
};

export default aliases;
export {
    AliasesInterface,
    TsconfigAliasesInterface,
    getTsconfigAliases,
    getWebpackResolveAliases,
    getLocalTsconfigAliases,
    getLocalWebpackResolveAliases,
    getLocalWebpackResolveModules,
};
