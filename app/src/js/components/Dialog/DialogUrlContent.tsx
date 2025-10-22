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
import { useDialog, resolveDialogPayload, ResolveDialogPayloadType } from "@arteneo/forge";
import Pre from "~app/components/Common/Pre";

interface DialogUrlContentProps {
    url: ResolveDialogPayloadType<string>;
}

const DialogUrlContent = ({ url }: DialogUrlContentProps) => {
    const { payload, initialized } = useDialog();

    const resolvedUrl = resolveDialogPayload<string>(url, payload, initialized);

    return <Pre {...{ content: resolvedUrl }} />;
};

export default DialogUrlContent;
export { DialogUrlContentProps };
