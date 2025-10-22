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
import SelectApiGroupedByDevice, {
    SelectApiGroupedByDeviceProps,
} from "~app/components/Form/fields/SelectApiGroupedByDevice";

type SelectApiGroupedByDeviceFilterProps = SelectApiGroupedByDeviceProps;

/**
 * Read about requirements in <SelectApiGroupedByDevice /> component.
 */
const SelectApiGroupedByDeviceFilter = (props: SelectApiGroupedByDeviceFilterProps) => {
    return <SelectApiGroupedByDevice {...props} />;
};

// * It has to be done via .defaultProps so filterType is passed openly to this component and can be read by Table context
SelectApiGroupedByDeviceFilter.defaultProps = {
    filterType: "equal",
};

export default SelectApiGroupedByDeviceFilter;
export { SelectApiGroupedByDeviceFilterProps };
