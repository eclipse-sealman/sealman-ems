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
import { UploadSingleInputFile } from "@arteneo/forge-uppy";
import {
    getFields,
    Text,
    Collection,
    MultiselectApi,
    RadioEnum,
    SelectEnum,
    MultiselectApiProps,
    Checkbox,
    CheckboxProps,
    RadioFalseTrue,
} from "@arteneo/forge";
import { FormikValues, getIn } from "formik";
import { showAndRequireOnEqual, showOnEqual } from "~app/utilities/fields";
import {
    MicrosoftOidcCredentialType,
    MicrosoftOidcRoleType,
    microsoftOidcCredential,
    microsoftOidcRole,
    singleSignOn,
} from "~app/entities/Configuration/enums";
import { uppyTusOptions } from "~app/utilities/uppy";
import TextWithObfuscatedValue from "~app/components/Form/fields/TextWithObfuscatedValue";

const accessTagsSupported = (microsoftOidcRole: MicrosoftOidcRoleType) => {
    if (microsoftOidcRole === "smartems" || microsoftOidcRole === "vpn" || microsoftOidcRole === "smartemsVpn") {
        return true;
    }

    return false;
};

const roleVpnEndpointDevicesSupported = (microsoftOidcRole: MicrosoftOidcRoleType) => {
    if (microsoftOidcRole === "vpn" || microsoftOidcRole === "smartemsVpn") {
        return true;
    }

    return false;
};

const accessTagsDisabled: MultiselectApiProps["disabled"] = (values, touched, errors, name) => {
    const nameParts = name.split(".");
    nameParts.pop();

    const microsoftOidcRolePath = nameParts.join(".") + ".microsoftOidcRole";
    const microsoftOidcRole = getIn(values, microsoftOidcRolePath);

    if (accessTagsSupported(microsoftOidcRole)) {
        return false;
    }

    return true;
};

const roleVpnEndpointDevicesDisabled: CheckboxProps["disabled"] = (values, touched, errors, name) => {
    const nameParts = name.split(".");
    nameParts.pop();

    const microsoftOidcRolePath = nameParts.join(".") + ".microsoftOidcRole";
    const microsoftOidcRole = getIn(values, microsoftOidcRolePath);

    if (roleVpnEndpointDevicesSupported(microsoftOidcRole)) {
        return false;
    }

    return true;
};

export const showAndRequireOnCredential = (value?: MicrosoftOidcCredentialType) => ({
    hidden: (values: FormikValues) =>
        values?.["singleSignOn"] !== "microsoftOidc" || values?.["microsoftOidcCredential"] !== value,
    required: (values: FormikValues) =>
        values?.["singleSignOn"] === "microsoftOidc" && values?.["microsoftOidcCredential"] === value,
});

export const showOnCredential = (value?: MicrosoftOidcCredentialType) => ({
    hidden: (values: FormikValues) =>
        values?.["singleSignOn"] !== "microsoftOidc" || values?.["microsoftOidcCredential"] !== value,
});

const fields = {
    singleSignOn: <RadioEnum required enum={singleSignOn} />,
    ssoAllowCustomRedirectUrl: <RadioFalseTrue {...{ help: true, ...showOnEqual("singleSignOn", "microsoftOidc") }} />,
    ssoRoleVpnCertificateAutoGenerate: <Checkbox {...showOnEqual("singleSignOn", "microsoftOidc")} />,
    microsoftOidcAppId: <Text {...showAndRequireOnEqual("singleSignOn", "microsoftOidc")} />,
    microsoftOidcDirectoryId: <Text {...showAndRequireOnEqual("singleSignOn", "microsoftOidc")} />,
    microsoftOidcTimeout: <Text {...{ help: true, ...showAndRequireOnEqual("singleSignOn", "microsoftOidc") }} />,
    microsoftOidcCredential: (
        <RadioEnum {...{ enum: microsoftOidcCredential, ...showAndRequireOnEqual("singleSignOn", "microsoftOidc") }} />
    ),
    microsoftOidcClientSecret: <TextWithObfuscatedValue {...showAndRequireOnCredential("clientSecret")} />,
    // required logic is moved to fieldset due to useConfiguration() context requirements
    microsoftOidcUploadedCertificatePublic: (
        <UploadSingleInputFile {...{ uppyTusOptions, ...showOnCredential("certificateUpload") }} />
    ),
    // required logic is moved to fieldset due to useConfiguration() context requirements
    microsoftOidcUploadedCertificatePrivate: (
        <UploadSingleInputFile {...{ uppyTusOptions, ...showOnCredential("certificateUpload") }} />
    ),
    // required logic is moved to fieldset due to useConfiguration() context requirements
    microsoftOidcGenerateCertificate: <Checkbox {...{ ...showOnCredential("certificateGenerate") }} />,
    microsoftOidcGenerateCertificateExpiryDays: (
        <Text
            {...{
                hidden: (values: FormikValues) =>
                    values?.["singleSignOn"] !== "microsoftOidc" ||
                    values?.["microsoftOidcCredential"] !== "certificateGenerate" ||
                    !values?.["microsoftOidcGenerateCertificate"],
                required: (values: FormikValues) =>
                    values?.["singleSignOn"] === "microsoftOidc" &&
                    values?.["microsoftOidcCredential"] === "certificateGenerate" &&
                    values?.["microsoftOidcGenerateCertificate"],
            }}
        />
    ),
    microsoftOidcRoleMappings: (
        <Collection
            {...{
                hidden: (values) => values?.["singleSignOn"] !== "microsoftOidc",
                fields: {
                    roleName: <Text required />,
                    microsoftOidcRole: (
                        <SelectEnum
                            required
                            enum={microsoftOidcRole}
                            onChange={(path, setFieldValue, value, onChange) => {
                                onChange();

                                const nameParts = path.split(".");
                                nameParts.pop();
                                const namePrefix = nameParts.join(".");

                                // eslint-disable-next-line
                                const microsoftOidcRole = (value as any)?.id as MicrosoftOidcRoleType;

                                if (!accessTagsSupported(microsoftOidcRole)) {
                                    const accessTagsPath = namePrefix + ".accessTags";
                                    setFieldValue(accessTagsPath, []);
                                }

                                if (!roleVpnEndpointDevicesSupported(microsoftOidcRole)) {
                                    const roleVpnEndpointDevicesPath = namePrefix + ".roleVpnEndpointDevices";
                                    setFieldValue(roleVpnEndpointDevicesPath, false);
                                }
                            }}
                        />
                    ),
                    accessTags: <MultiselectApi disabled={accessTagsDisabled} endpoint="/options/access/tags" />,
                    roleVpnEndpointDevices: <Checkbox disabled={roleVpnEndpointDevicesDisabled} />,
                },
            }}
        />
    ),
};

export default getFields(fields);
