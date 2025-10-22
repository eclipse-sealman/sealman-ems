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
import { Optional } from "@arteneo/forge";
import { useDetails } from "~app/contexts/Details";
import ResultDuplicate, { ResultDuplicateProps } from "~app/components/Table/actions/ResultDuplicate";

type TemplateVersionResultDuplicateProps = Optional<ResultDuplicateProps, "dialogProps">;

const TemplateVersionResultDuplicate = (props: TemplateVersionResultDuplicateProps) => {
    const { reload: detailsReload } = useDetails();

    return (
        <ResultDuplicate
            {...{
                dialogProps: (result) => ({
                    confirmProps: {
                        endpoint: "/templateversion/" + result?.id + "/duplicate",
                        onSuccess: (defaultOnSuccess) => {
                            defaultOnSuccess();
                            detailsReload();
                        },
                    },
                }),
                ...props,
            }}
        />
    );
};

export default TemplateVersionResultDuplicate;
export { TemplateVersionResultDuplicateProps };
