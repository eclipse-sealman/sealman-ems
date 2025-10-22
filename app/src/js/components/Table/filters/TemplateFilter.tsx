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
import SelectApiGroupedByDeviceTypeFilter, {
    SelectApiGroupedByDeviceTypeFilterProps,
} from "~app/components/Table/filters/SelectApiGroupedByDeviceTypeFilter";

/**
 * Read about requirements in <SelectApiGroupedByDeviceType /> component.
 */
const TemplateFilter = (props: SelectApiGroupedByDeviceTypeFilterProps) => {
    return <SelectApiGroupedByDeviceTypeFilter {...props} />;
};

// * It has to be done via .defaultProps so filterType is passed openly to this component and can be read by Table context
TemplateFilter.defaultProps = {
    filterType: "equal",
};

export default TemplateFilter;
