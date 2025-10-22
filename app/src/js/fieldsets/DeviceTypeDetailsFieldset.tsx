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
import DeviceTypeFieldsetContent, {
    DeviceTypeFieldsetContentProps,
} from "~app/fieldsets/content/DeviceTypeFieldsetContent";
import CrudFormDetailsView, { CrudFormDetailsViewProps } from "~app/views/CrudFormDetailView";

type DeviceTypeDetailsFieldsetProps = DeviceTypeFieldsetContentProps & Omit<CrudFormDetailsViewProps, "children">;

const DeviceTypeDetailsFieldset = ({
    fields,
    disableAll,
    enableConfigMinRsrp,
    enableFirmwareMinRsrp,
    noCommunicationFields,
    ...formViewProps
}: DeviceTypeDetailsFieldsetProps) => {
    return (
        <CrudFormDetailsView {...formViewProps}>
            <DeviceTypeFieldsetContent
                {...{ fields, disableAll, enableConfigMinRsrp, enableFirmwareMinRsrp, noCommunicationFields }}
            />
        </CrudFormDetailsView>
    );
};

export default DeviceTypeDetailsFieldset;
export { DeviceTypeDetailsFieldsetProps };
