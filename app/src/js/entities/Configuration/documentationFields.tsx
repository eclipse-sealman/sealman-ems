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
import { FieldsInterface, getFields } from "@arteneo/forge";
import RadioDisableDocumentation from "~app/components/Form/fields/RadioDisableDocumentation";

const composeGetFields = (isVpnAvailable: boolean) => {
    const fields: FieldsInterface = {
        disableAdminRestApiDocumentation: (
            <RadioDisableDocumentation
                {...{ buttonLink: "/web/doc/admin", buttonLabel: "configuration.documentation.radioButton.admin" }}
            />
        ),
        disableSmartemsRestApiDocumentation: (
            <RadioDisableDocumentation
                {...{
                    buttonLink: "/web/doc/smartems",
                    buttonLabel: "configuration.documentation.radioButton.smartems",
                }}
            />
        ),
    };

    if (isVpnAvailable) {
        fields["disableVpnSecuritySuiteRestApiDocumentation"] = (
            <RadioDisableDocumentation
                {...{
                    buttonLink: "/web/doc/vpnsecuritysuite",
                    buttonLabel: "configuration.documentation.radioButton.vpnsecuritysuite",
                }}
            />
        );
    }

    return getFields(fields);
};

export default composeGetFields;
