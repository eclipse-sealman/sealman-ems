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
import {
    ColumnActionPathInterface,
    useTable,
    ResultInterface,
    DenyBehaviorType,
    ButtonDialogBatchAlertConfirmMulti,
    ButtonDialogBatchAlertConfirmMultiProps,
    Optional,
} from "@arteneo/forge";
import { LinkOutlined } from "@mui/icons-material";
import { useDetails } from "~app/contexts/Details";

type ResultVpnOpenAllConnectionsProps = Optional<ButtonDialogBatchAlertConfirmMultiProps, "dialogProps"> &
    ColumnActionPathInterface;

const ResultVpnOpenAllConnections = ({ result, dialogProps, ...props }: ResultVpnOpenAllConnectionsProps) => {
    const { reload: reloadTable } = useTable();
    const { reload: reloadDetails } = useDetails();

    const [finished, setFinished] = React.useState(false);

    if (typeof result === "undefined") {
        throw new Error("ResultVpnOpenAllConnections component: Missing required result prop");
    }

    let denyBehavior: DenyBehaviorType = "disable";
    if (result?.deny?.vpnOpenConnection && result?.deny?.vpnOpenConnection.endsWith("alreadyConnected")) {
        denyBehavior = "hide";
    }

    const endpointDevices: ResultInterface[] = result?.endpointDevices ?? [];
    if (endpointDevices.length === 0) {
        return null;
    }

    const selectedResults: ResultInterface[] = [result];
    selectedResults.push(
        ...endpointDevices.map((endpointDevice) => ({
            // I need to include information whether this result is an endpoint device to determine the endpoint
            type: "endpointDevice",
            ...endpointDevice,
        }))
    );

    return (
        <ButtonDialogBatchAlertConfirmMulti
            {...{
                size: "small",
                startIcon: <LinkOutlined />,
                deny: result.deny,
                denyBehavior: denyBehavior,
                denyKey: "vpnOpenConnection",
                label: "action.vpnOpenAllConnections",
                color: "info",
                variant: "contained",
                dialogProps: {
                    label: "resultVpnOpenAllConnections.dialog.confirm",
                    labelVariables: {
                        representation: result.representation,
                    },
                    onClose: (defaultOnClose) => {
                        defaultOnClose();
                        // Details needs to be reloaded on close. We cannot do it onFinish as the dialog will close itself which will prevent user from seeing the results of our action
                        if (finished) {
                            setFinished(false);
                            reloadDetails();
                        }
                    },
                    results: selectedResults,
                    ...dialogProps,
                    confirmProps: {
                        endpoint: (result) => {
                            if (result.type === "endpointDevice") {
                                return "/deviceendpointdevice/" + result.id + "/open/vpnconnection";
                            }

                            return "/device/" + result.id + "/open/vpnconnection";
                        },
                        resultDenyKey: "vpnOpenConnection",
                        ...dialogProps?.confirmProps,
                        onFinish: (defaultOnFinish, setLoading, cancelled) => {
                            const internalDefaultOnFinish = () => {
                                defaultOnFinish();
                                reloadTable();
                                setFinished(true);
                            };

                            if (typeof dialogProps?.confirmProps?.onFinish !== "undefined") {
                                dialogProps.confirmProps.onFinish(internalDefaultOnFinish, setLoading, cancelled);
                                return;
                            }

                            internalDefaultOnFinish();
                        },
                    },
                },
                ...props,
            }}
        />
    );
};

export default ResultVpnOpenAllConnections;
export { ResultVpnOpenAllConnectionsProps };
