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
import { getFields, RadioEnum, Text, RadioFalseTrue } from "@arteneo/forge";
import { totpAlgorithm, totpWindow, totpSecretLength } from "~app/entities/Configuration/enums";
import { FormikValues, useFormikContext } from "formik";
import { useTranslation } from "react-i18next";

const TotpWindowHelp = () => {
    const { t } = useTranslation();
    const { values } = useFormikContext<FormikValues>();

    const tokenKeyRegeneration = parseInt(values?.totpKeyRegeneration);

    const highlightSelected = (totpWindow: string, help: React.ReactNode) => {
        if (totpWindow === values?.totpWindow) {
            return <strong>{help}</strong>;
        }

        return help;
    };

    return (
        <>
            {!isNaN(tokenKeyRegeneration) ? (
                <>
                    {highlightSelected(
                        "1",
                        t("help.totpWindowInterval1", {
                            totpWindowLabel: t(totpWindow.getLabel("1")),
                            interval: tokenKeyRegeneration,
                        })
                    )}
                    <br />
                    {highlightSelected(
                        "3",
                        t("help.totpWindowInterval3", {
                            totpWindowLabel: t(totpWindow.getLabel("3")),
                            intervalStart: -tokenKeyRegeneration,
                            intervalFinish: 2 * tokenKeyRegeneration,
                        })
                    )}
                    <br />
                    {highlightSelected(
                        "5",
                        t("help.totpWindowInterval5", {
                            totpWindowLabel: t(totpWindow.getLabel("5")),
                            intervalStart: -2 * tokenKeyRegeneration,
                            intervalFinish: 3 * tokenKeyRegeneration,
                        })
                    )}
                </>
            ) : (
                <>{t("help.totpWindow")}</>
            )}
        </>
    );
};

const hidden = (values: FormikValues) => (values?.totpEnabled ? false : true);
const required = (values: FormikValues) => (values?.totpEnabled ? true : false);

const composeGetFields = (isTotpSecretGenerated: boolean) => {
    const fields = {
        totpEnabled: <RadioFalseTrue />,
        totpKeyRegeneration: <Text {...{ required, hidden, disabled: isTotpSecretGenerated, help: true }} />,
        totpWindow: <RadioEnum {...{ required, hidden, enum: totpWindow, help: <TotpWindowHelp /> }} />,
        totpTokenLength: <Text {...{ required, hidden, disabled: isTotpSecretGenerated, help: true }} />,
        totpSecretLength: (
            <RadioEnum {...{ required, hidden, disabled: isTotpSecretGenerated, enum: totpSecretLength }} />
        ),
        totpAlgorithm: (
            <RadioEnum {...{ required, hidden, disabled: isTotpSecretGenerated, enum: totpAlgorithm, help: true }} />
        ),
    };

    return getFields(fields);
};

export default composeGetFields;
export { TotpWindowHelp };
