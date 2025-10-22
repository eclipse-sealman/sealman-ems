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
import { FieldsInterface, renderField } from "@arteneo/forge";
import { Box } from "@mui/material";
import { useFormikContext } from "formik";
import useDeepCompareEffect from "use-deep-compare-effect";

interface ColumnFieldsetProps {
    fields: FieldsInterface;
    minWidth?: number;
}

const ColumnFieldset = ({ fields, minWidth }: ColumnFieldsetProps) => {
    const mounted = React.useRef(false);

    const { values, submitForm } = useFormikContext();

    const render = renderField(fields);

    useDeepCompareEffect(() => {
        // Prevent submitting on first render
        if (mounted?.current) {
            submitForm();
            return;
        }

        mounted.current = true;
    }, [values]);

    return <Box {...{ sx: { display: "grid", minWidth } }}>{Object.keys(fields).map((field) => render(field))}</Box>;
};

export default ColumnFieldset;
export { ColumnFieldsetProps };
