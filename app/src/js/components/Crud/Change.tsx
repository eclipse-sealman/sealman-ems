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
import { Form, FormProps, Optional } from "@arteneo/forge";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import SurfaceTitle, { SurfaceTitleProps } from "~app/components/Common/SurfaceTitle";

interface ChangeProps extends Optional<FormProps, "endpoint" | "children"> {
    endpoint: string;
    titleProps: SurfaceTitleProps;
}

const Change = ({ endpoint, titleProps, ...formProps }: ChangeProps) => {
    return (
        <>
            <SurfaceTitle {...{ subtitle: "route.subtitle.change", ...titleProps }} />
            <Surface>
                <Form
                    {...{
                        initializeEndpoint: endpoint,
                        endpoint,
                        children: <CrudFieldset {...{ fields: formProps.fields }} />,
                        ...formProps,
                    }}
                />
            </Surface>
        </>
    );
};

export default Change;
export { ChangeProps };
