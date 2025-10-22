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
import { SettingsOutlined } from "@mui/icons-material";
import { DeviceInterface } from "~app/entities/Device/definitions";
import ButtonExpand from "~app/components/Common/ButtonExpand";
import ResultDialogMonaco from "~app/components/Table/actions/ResultDialogMonaco";

interface ShowConfigExpandProps {
    device: DeviceInterface;
}

const ShowConfigExpand = ({ device }: ShowConfigExpandProps) => {
    const deviceType = device.deviceType;

    return (
        <ButtonExpand
            {...{
                label: "action.configs",
                deny: device?.deny,
                denyKey: "showConfigExpand",
                denyBehavior: "hide",
                startIcon: <SettingsOutlined />,
            }}
        >
            {deviceType.hasConfig1 && (
                <ResultDialogMonaco
                    {...{
                        label: "action.showConfig",
                        labelVariables: {
                            configName: deviceType.nameConfig1,
                        },
                        result: device,
                        denyKey: "generateConfigPrimary",
                        denyBehavior: "disable",
                        dialogProps: (result) => ({
                            initializeEndpoint: "/device/" + result.id + "/generate/config/primary",
                            content: (payload) => payload,
                            language: deviceType.formatConfig1,
                        }),
                    }}
                />
            )}
            {deviceType.hasConfig2 && (
                <ResultDialogMonaco
                    {...{
                        label: "action.showConfig",
                        labelVariables: {
                            configName: deviceType.nameConfig2,
                        },
                        result: device,
                        denyKey: "generateConfigSecondary",
                        denyBehavior: "disable",
                        dialogProps: (result) => ({
                            initializeEndpoint: "/device/" + result.id + "/generate/config/secondary",
                            content: (payload) => payload,
                            language: deviceType.formatConfig2,
                        }),
                    }}
                />
            )}
            {deviceType.hasConfig3 && (
                <ResultDialogMonaco
                    {...{
                        label: "action.showConfig",
                        labelVariables: {
                            configName: deviceType.nameConfig3,
                        },
                        result: device,
                        denyKey: "generateConfigTertiary",
                        denyBehavior: "disable",
                        dialogProps: (result) => ({
                            initializeEndpoint: "/device/" + result.id + "/generate/config/tertiary",
                            content: (payload) => payload,
                            language: deviceType.formatConfig3,
                        }),
                    }}
                />
            )}
        </ButtonExpand>
    );
};

export default ShowConfigExpand;
export { ShowConfigExpandProps };
