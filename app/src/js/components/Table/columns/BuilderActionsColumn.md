<!--
Copyright (c) 2025 Contributors to the Eclipse Foundation.

See the NOTICE file(s) distributed with this work for additional
information regarding copyright ownership.

This program and the accompanying materials are made available under the
terms of the Apache License, Version 2.0 which is available at
https://www.apache.org/licenses/LICENSE-2.0

SPDX-License-Identifier: Apache-2.0
-->

# BuilderActionsColumn

## Example 1

This will use default render which will show editAction and deleteAction (in that order)

```ts
import React from "react";
import { getColumns, TextColumn } from "@arteneo/forge";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";

type ColumnName = "name" | "actions";

const columns = {
    name: <TextColumn />,
    actions: <BuilderActionsColumn />,
};

export default (names?: ColumnName[]) => getColumns<ColumnName>(names, columns);
export { ColumnName };
```

## Example 2

This will customize render which will allow you to render elements as you whish. Each child will have `result` and `columnName` props injected similar to the way `ActionsColumn` work.

```ts
import React from "react";
import { getColumns, TextColumn } from "@arteneo/forge";
import BuilderActionsColumn from "~app/components/Table/columns/BuilderActionsColumn";

type ColumnName = "name" | "actions";

const columns = {
    name: <TextColumn />,
    actions: (
        <BuilderActionsColumn
            {...{
                render: ({ editAction, duplicateAction, deleteAction }) => (
                    <>
                        <div>Custom things</div>
                        {editAction}
                        <div>Other things</div>
                        {duplicateAction}
                        {deleteAction}
                    </>
                ),
            }}
        />
    ),
};

export default (names?: ColumnName[]) => getColumns<ColumnName>(names, columns);
export { ColumnName };
```
