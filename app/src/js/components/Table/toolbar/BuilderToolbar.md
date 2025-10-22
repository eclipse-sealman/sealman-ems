<!--
Copyright (c) 2025 Contributors to the Eclipse Foundation.

See the NOTICE file(s) distributed with this work for additional
information regarding copyright ownership.

This program and the accompanying materials are made available under the
terms of the Apache License, Version 2.0 which is available at
https://www.apache.org/licenses/LICENSE-2.0

SPDX-License-Identifier: Apache-2.0
-->

# BuilderToolbar

## Example 1

When toolbar is skipped it in `listProps`, `BuilderToolbar` will be used with default render which will show createAction

```ts
import React from "react";
import getColumns from "~app/entities/AccessTag/columns";
import getFilters from "~app/entities/AccessTag/filters";
import getFields from "~app/entities/AccessTag/fields";
import Builder from "~app/components/Crud/Builder";

const AccessTag = () => {
    const columns = getColumns();
    const filters = getFilters();
    const fields = getFields();

    return (
        <Builder
            {...{
                endpointPrefix: "/accesstag",
                listProps: {
                    columns,
                    filters,
                },
                createProps: {
                    fields,
                },
                editProps: {
                    fields,
                },
                deleteProps: {},
            }}
        />
    );
};

export default AccessTag;
```

## Example 2

This will customize `BuilderToolbar` render which will allow you to render elements as you whish.

```ts
import React from "react";
import getColumns from "~app/entities/AccessTag/columns";
import getFilters from "~app/entities/AccessTag/filters";
import getFields from "~app/entities/AccessTag/fields";
import Builder from "~app/components/Crud/Builder";
import BuilderToolbar from "~app/components/Table/toolbar/BuilderToolbar";

const AccessTag = () => {
    const columns = getColumns();
    const filters = getFilters();
    const fields = getFields();

    return (
        <Builder
            {...{
                endpointPrefix: "/accesstag",
                listProps: {
                    columns,
                    filters,
                    toolbar: (
                        <BuilderToolbar
                            {...{
                                render: (createAction) => (
                                    <>
                                        <div>My stuff!</div>
                                        {createAction}
                                        <div>My stuff 2!</div>
                                    </>
                                ),
                            }}
                        />
                    ),
                },
                createProps: {
                    fields,
                },
                editProps: {
                    fields,
                },
                deleteProps: {},
            }}
        />
    );
};

export default AccessTag;
```
