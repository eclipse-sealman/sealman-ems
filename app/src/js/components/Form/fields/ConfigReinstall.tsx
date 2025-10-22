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
import { FeatureType } from "~app/enums/Feature";

interface ConfigReinstallProps extends ReinstallProps {
    configId: string | number;
    feature: FeatureType;
    contentPath?: string;
}

const ConfigReinstall = ({
    configId,
    feature,
    contentPath = "content",
    label = "connectedDevicesReinstallConfig",
    ...props
}: ConfigReinstallProps) => {
    const { formikInitialValues } = useForm();
    const { values } = useFormikContext();

    const initialContent = getIn(formikInitialValues, contentPath);
    const content = getIn(values, contentPath);

    if (content === initialContent) {
        return null;
    }

    return (
        <Reinstall
            {...{
                label,
                ...props,
                tableReinstallProps: {
                    additionalFilters: {
                        config: {
                            filterBy: "config" + feature,
                            filterType: "equal",
                            filterValue: configId,
                        },
                    },
                    ...(props.tableReinstallProps ?? {}),
                },
            }}
        />
    );
};

export default ConfigReinstall;
export { ConfigReinstallProps };
