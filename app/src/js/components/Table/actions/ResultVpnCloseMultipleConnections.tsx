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
    ButtonDialogBatchFormMultiAlertFieldset,
    ButtonDialogBatchFormMultiAlertFieldsetProps,
    Checkbox,
    ColumnActionInterface,
    FieldsInterface,
    Optional,
    ResultInterface,
    useTable,
} from "@arteneo/forge";
import { LinkOffOutlined } from "@mui/icons-material";
import { useTranslation } from "react-i18next";
import { useDetails } from "~app/contexts/Details";

type ResultVpnCloseMultipleConnectionsProps = Optional<ButtonDialogBatchFormMultiAlertFieldsetProps, "dialogProps"> &
    ColumnActionInterface;

const ResultVpnCloseMultipleConnections = ({ result, ...props }: ResultVpnCloseMultipleConnectionsProps) => {
    const { t } = useTranslation();
    const { reload: reloadTable } = useTable();
    const { reload: reloadDetails } = useDetails();

    const [finished, setFinished] = React.useState(false);

    if (typeof result === "undefined") {
        throw new Error("ResultVpnCloseMultipleConnections component: Missing required result prop");
    }

    const vpnConnections: ResultInterface[] = result?.vpnConnections?.slice() ?? [];
    const endpointDevices: ResultInterface[] = result?.endpointDevices ?? [];
    endpointDevices.forEach((endpointDevice) => {
        vpnConnections.push(...(endpointDevice?.vpnConnections?.slice() ?? []));
    });

    if (vpnConnections.length === 0) {
        return null;
    }

    const getRepresentation = (vpnConnection: ResultInterface) => {
        return t("resultVpnCloseMultipleConnections.dialog.result", {
            user: vpnConnection.user?.representation,
            target: vpnConnection.target?.representation,
        });
    };

    const prefix = "vpnConnection";
    const fields: FieldsInterface = {};

    vpnConnections.forEach((vpnConnection) => {
        fields[prefix + vpnConnection.id] = (
            <Checkbox
                {...{
                    label: getRepresentation(vpnConnection),
                    disableAutoLabel: true,
                    disableTranslateLabel: true,
                }}
            />
        );
    });

    const results = vpnConnections.map((vpnConnection) => ({
        ...vpnConnection,
        // Adjust representation
        representation: getRepresentation(vpnConnection),
    }));

    return (
        <ButtonDialogBatchFormMultiAlertFieldset
            {...{
                size: "small",
                startIcon: <LinkOffOutlined />,
                label: "action.vpnCloseMultipleConnections",
                color: "error",
                variant: "contained",
                dialogProps: {
                    results,
                    title: "resultVpnCloseMultipleConnections.dialog.title",
                    label: "resultVpnCloseMultipleConnections.dialog.label",
                    labelVariables: { user: result.user?.representation, target: result.target?.representation },
                    onClose: (defaultOnClose) => {
                        defaultOnClose();
                        // Details needs to be reloaded on close. We cannot do it onFinish as the dialog will close itself which will prevent user from seeing the results of our action
                        if (finished) {
                            setFinished(false);
                            reloadDetails();
                            reloadTable();
                        }
                    },
                    formProps: {
                        fields,
                        endpoint: (result, values) => {
                            if (!values?.[prefix + result.id]) {
                                return;
                            }

                            return {
                                method: "get",
                                url: "/vpnconnection/" + result.id + "/close/vpnconnection",
                            };
                        },
                        onSubmitFinish: (defaultOnSubmitFinish) => {
                            defaultOnSubmitFinish();
                            setFinished(true);
                        },
                    },
                },
                ...props,
            }}
        />
    );
};

export default ResultVpnCloseMultipleConnections;
export { ResultVpnCloseMultipleConnectionsProps };
