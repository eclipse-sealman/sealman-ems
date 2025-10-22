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
import { FilterFieldInterface } from "@arteneo/forge";
import MultiselectDeviceTypeApi, {
    MultiselectDeviceTypeApiProps,
} from "~app/components/Form/fields/MultiselectDeviceTypeApi";

type MultiselectDeviceTypeFilterProps = FilterFieldInterface & MultiselectDeviceTypeApiProps;

//This filter field is designed to use only with deviceType options where color and icon are available (additional identification group added to DeviceType entity)
// filterBy and filterType are destructed to avoid passing them deeper
// eslint-disable-next-line
const MultiselectDeviceTypeFilter = ({ filterBy, filterType, ...props }: MultiselectDeviceTypeFilterProps) => {
    return (
        <MultiselectDeviceTypeApi
            {...{
                ...props,
            }}
        />
    );
};

// * It has to be done via .defaultProps so filterType is passed openly to this component and can be read by Table context
MultiselectDeviceTypeFilter.defaultProps = {
    filterType: "equalMultiple",
};

export default MultiselectDeviceTypeFilter;
