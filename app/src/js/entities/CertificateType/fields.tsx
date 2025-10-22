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
import { Text, getFields, RadioFalseTrue, RadioEnum } from "@arteneo/forge";
import TextCrl from "~app/components/Form/fields/TextCrl";
import {
    certificateBehavior,
    certificateBehaviorLimited,
    certificateEntity,
    pkiType,
    pkiHashAlgorithm,
    pkiKeyLength,
} from "~app/entities/CertificateType/enums";
import { showOnEqual, showAndRequireOnEqual, showOnTrue } from "~app/utilities/fields";
import TextWithObfuscatedValue from "~app/components/Form/fields/TextWithObfuscatedValue";

// Limited edit is for non-custom certificates
// Predefined certificates (eg. DeviceVpn, EdgeCa), have to behave as designed - hence some fields are disabled
const composeGetFields = (predefinedCertificateCategory: boolean, editAction: boolean) => {
    const fields = {
        //hidden field used for limited edit
        certificateCategory: <Text {...{ hidden: true }} />,
        name: <Text {...{ required: true, disabled: predefinedCertificateCategory }} />,
        certificateEntity: (
            <RadioEnum
                {...{
                    enum: certificateEntity,
                    help: true,
                    disabled: predefinedCertificateCategory || editAction,
                }}
            />
        ),
        commonNamePrefix: (
            <Text {...{ required: !predefinedCertificateCategory, disabled: predefinedCertificateCategory }} />
        ),
        variablePrefix: (
            <Text {...{ required: !predefinedCertificateCategory, disabled: predefinedCertificateCategory }} />
        ),
        enabled: <RadioFalseTrue />,
        downloadEnabled: <RadioFalseTrue />,
        uploadEnabled: <RadioFalseTrue />,
        deleteEnabled: <RadioFalseTrue {...{ ...showOnTrue("uploadEnabled") }} />,
        pkiEnabled: <RadioFalseTrue />,
        enabledBehaviour: (
            <RadioEnum
                {...{
                    enum: predefinedCertificateCategory ? certificateBehavior : certificateBehaviorLimited,
                    help: true,
                    disabled: predefinedCertificateCategory,
                }}
            />
        ),
        disabledBehaviour: (
            <RadioEnum
                {...{
                    enum: predefinedCertificateCategory ? certificateBehavior : certificateBehaviorLimited,
                    help: true,
                    disabled: predefinedCertificateCategory,
                }}
            />
        ),
        pkiType: (
            <RadioEnum
                {...{
                    enum: pkiType,
                }}
            />
        ),
        scepVerifyServerSslCertificate: <RadioFalseTrue {...{ ...showOnEqual("pkiType", "scep") }} />,
        scepUrl: <Text {...{ ...showAndRequireOnEqual("pkiType", "scep"), help: true }} />,
        scepCrlUrl: (
            <TextCrl
                {...{
                    ...showAndRequireOnEqual("pkiType", "scep"),
                    verifyServerSslCertificatePath: "scepVerifyServerSslCertificate",
                    scepTimeoutPath: "scepTimeout",
                    help: true,
                }}
            />
        ),
        scepRevocationUrl: <Text {...{ ...showAndRequireOnEqual("pkiType", "scep"), help: true }} />,
        scepTimeout: <Text {...{ ...showAndRequireOnEqual("pkiType", "scep"), help: true }} />,
        scepRevocationBasicAuthUser: <Text {...{ ...showOnEqual("pkiType", "scep"), help: true }} />,
        scepRevocationBasicAuthPassword: (
            <TextWithObfuscatedValue {...{ ...showOnEqual("pkiType", "scep"), help: true }} />
        ),
        scepHashFunction: (
            <RadioEnum
                {...{
                    enum: pkiHashAlgorithm,
                    ...showAndRequireOnEqual("pkiType", "scep"),
                    help: true,
                }}
            />
        ),
        scepKeyLength: (
            <RadioEnum
                {...{
                    enum: pkiKeyLength,
                    ...showAndRequireOnEqual("pkiType", "scep"),
                    help: true,
                }}
            />
        ),
    };

    return getFields(fields);
};

export default composeGetFields;
