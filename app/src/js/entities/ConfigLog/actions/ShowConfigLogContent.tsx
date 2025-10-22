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
import { Optional } from "@arteneo/forge";
import ResultDialogMonaco, { ResultDialogMonacoProps } from "~app/components/Table/actions/ResultDialogMonaco";
import { MonacoLanguageType } from "~app/components/Dialog/DialogMonacoContent";
import { getFormatConfig } from "~app/entities/Config/utilities";
import { FeatureType } from "~app/enums/Feature";
import { DeviceTypeInterface } from "~app/entities/DeviceType/definitions";

type ShowConfigLogContentProps = Optional<ResultDialogMonacoProps, "dialogProps">;

const ShowConfigLogContent = ({ result, ...props }: ShowConfigLogContentProps) => {
    if (typeof result === "undefined") {
        throw new Error("ShowConfigLogContent component: Missing required result prop");
    }

    let language: MonacoLanguageType = "plain";
    const deviceType: undefined | DeviceTypeInterface = result?.device?.deviceType;
    const feature: undefined | FeatureType = result?.feature;
    if (typeof deviceType !== "undefined" && typeof feature !== "undefined") {
        language = getFormatConfig(deviceType, feature);
    }

    return (
        <ResultDialogMonaco
            {...{
                result,
                denyKey: "showContent",
                denyBehavior: "hide",
                label: "resultDialogContent.action",
                dialogProps: (result) => ({
                    title: "resultDialogContent.dialog.title",
                    initializeEndpoint: "/configlog/content/" + result.id,
                    content: (payload) => payload?.content,
                    language,
                }),
                ...props,
            }}
        />
    );
};

export default ShowConfigLogContent;
export { ShowConfigLogContentProps };
