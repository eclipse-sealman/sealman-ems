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
import { Text, getFields, Checkbox, MultiselectApi, SelectApi, RadioEnum } from "@arteneo/forge";
import { showAndRequireOn, showAndRequireOnTrue, showOn } from "~app/utilities/fields";
import { isGenerate, isRenew, secretValueBehaviour } from "~app/entities/DeviceTypeSecret/enums";
import { Trans } from "react-i18next";
import { FormikValues } from "formik";

const secretValueRenewAfterDaysRequired = (useAsVariable?: boolean, secretValueBehaviour?: string): boolean =>
    useAsVariable && isRenew(secretValueBehaviour) ? true : false;

const fields = {
    deviceType: <SelectApi {...{ required: true, disabled: true, endpoint: "/options/device/types" }} />,
    name: <Text {...{ required: true }} />,
    description: <Text {...{ required: true }} />,
    useAsVariable: <Checkbox {...{ help: true }} />,
    variableNamePrefix: <Text {...{ ...showAndRequireOnTrue("useAsVariable") }} />,
    secretValueBehaviour: (
        <RadioEnum
            {...{
                ...showAndRequireOnTrue("useAsVariable"),
                enum: secretValueBehaviour,
                help: <Trans i18nKey="help.secretValueBehaviour" />,
            }}
        />
    ),
    secretValueRenewAfterDays: (
        <Text
            {...{
                required: ({ useAsVariable, secretValueBehaviour }) =>
                    secretValueRenewAfterDaysRequired(useAsVariable, secretValueBehaviour),
                hidden: ({ useAsVariable, secretValueBehaviour }) =>
                    !secretValueRenewAfterDaysRequired(useAsVariable, secretValueBehaviour),
            }}
        />
    ),
    manualForceRenewal: (
        <Checkbox
            {...{
                hidden: ({ useAsVariable, secretValueBehaviour }) =>
                    useAsVariable && (isGenerate(secretValueBehaviour) || isRenew(secretValueBehaviour)) ? false : true,
            }}
        />
    ),
    manualEdit: <Checkbox />,
    manualEditRenewReminder: <Checkbox {...{ ...showOn({ manualEdit: true }), help: true }} />,
    manualEditRenewReminderAfterDays: (
        <Text {...{ ...showAndRequireOn({ manualEdit: true, manualEditRenewReminder: true }) }} />
    ),

    accessTags: <MultiselectApi {...{ endpoint: "/options/access/tags", help: "help.deviceTypeSecretAccessTags" }} />,

    secretMinimumLength: <Text {...{ required: true }} />,
    secretLowercaseLettersAmount: <Text {...{ required: true }} />,
    secretUppercaseLettersAmount: <Text {...{ required: true }} />,
    secretDigitsAmount: <Text {...{ required: true }} />,
    secretSpecialCharactersAmount: <Text {...{ required: true }} />,
};

const clearSubmitValues = (values: FormikValues): void => {
    if (!values.useAsVariable) {
        delete values.variableNamePrefix;
        delete values.secretValueBehaviour;
        delete values.secretValueRenewAfterDays;
    }

    if (!isRenew(values.secretValueBehaviour)) {
        delete values.secretValueRenewAfterDays;
    }

    if (values.secretValueBehaviour === "none") {
        delete values.manualForceRenewal;
    }

    if (!values.manualEdit) {
        delete values.manualEditRenewReminder;
        delete values.manualEditRenewReminderAfterDays;
    }

    if (!values.manualEditRenewReminder) {
        delete values.manualEditRenewReminderAfterDays;
    }
};

export default getFields(fields);
export { clearSubmitValues };
