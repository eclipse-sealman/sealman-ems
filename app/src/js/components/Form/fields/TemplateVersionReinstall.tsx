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
import { useForm } from "@arteneo/forge";
import { getIn, useFormikContext } from "formik";
import Reinstall, { ReinstallProps } from "~app/components/Form/fields/Reinstall";
import getColumns from "~app/entities/Device/reinstallColumns";

interface TemplateVersionReinstallProps extends ReinstallProps {
    configPath: string;
    templateId: string | number;
}

const TemplateVersionReinstall = ({ configPath, templateId, ...props }: TemplateVersionReinstallProps) => {
    const { formikInitialValues } = useForm();
    const { values } = useFormikContext();

    const initialConfig = getIn(formikInitialValues, configPath);
    const config = getIn(values, configPath);

    if (!config) {
        return null;
    }

    if (config === initialConfig) {
        return null;
    }

    const columns = getColumns(undefined, ["template", "staging"]);

    return (
        <Reinstall
            {...{
                ...props,
                tableReinstallProps: {
                    columns,
                    additionalFilters: {
                        template: {
                            filterBy: "template",
                            filterType: "equal",
                            filterValue: templateId,
                        },
                        staging: {
                            filterBy: "staging",
                            filterType: "boolean",
                            filterValue: true,
                        },
                    },
                    ...(props.tableReinstallProps ?? {}),
                },
            }}
        />
    );
};

export default TemplateVersionReinstall;
export { TemplateVersionReinstallProps };
