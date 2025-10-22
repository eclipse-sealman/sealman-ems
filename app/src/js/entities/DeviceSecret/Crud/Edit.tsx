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
import { useNavigate, useParams } from "react-router-dom";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import CrudEdit, { EditProps as CrudEditProps } from "~app/components/Crud/Edit";

const Edit = ({ endpointPrefix, titleProps, ...formProps }: CrudEditProps) => {
    const { id } = useParams();
    const navigate = useNavigate();
    const initializeEndpoint = endpointPrefix + "/" + id + "/show";

    return (
        <CrudEdit
            {...{
                endpointPrefix: endpointPrefix,
                initializeEndpoint: initializeEndpoint,
                titleProps: {
                    disableSubtitleTranslate: true,
                    hint: "route.hint.edit",
                    ...titleProps,
                },
                children: (
                    <CrudFieldset
                        {...{
                            fields: formProps.fields,
                        }}
                    />
                ),
                onSubmitSuccess: (defaultOnSubmitSuccess: () => void) => {
                    defaultOnSubmitSuccess();
                    navigate(-1);
                },
                ...formProps,
            }}
        />
    );
};

export default Edit;
export { CrudEditProps as EditProps };
